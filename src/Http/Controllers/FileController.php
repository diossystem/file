<?php

namespace Belca\File\Http\Controllers;

use Belca\File\Contracts\FileRepository;
use Belca\File\Contracts\FileController as FileControllerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Belca\File\Http\Requests\FileRequest;
use Belca\Support\Config;
use Belca\FileHandler\FileHandler;
use Belca\GeName\GeName;
use Belca\File\Http\Services\FileService;

/**
 * Базовая загрузка файлов и управление ими.
 */
class FileController
{
    protected $disk = 'public';

    protected $filenamePattern;

    /**
     * Сервис для работы с файлами.
     *
     * @var \Belca\File\Http\Services\FileService;
     */
    protected $service;

    public function __construct(FileRepository $files)
    {
        $this->files = $files;

        $this->filenamePattern = config('file.filename_pattern');

        $this->service = new FileService($files);
    }

    /**
     * Отображает страницу с оригинальными файлами и форму для фильтрации
     * результата.
     *
     * @param  mixed $request Условия фильтрации
     * @return View
     */
    public function index(Request $request)
    {
        return view('belca-file::files.index')->with([
            'files' => $this->service->getFiles(true, $request->input()),
        ]);
    }

    /**
     * Отображает форму загрузки нового файла.
     *
     * @return View
     */
    public function create()
    {
        return view('belca-file::files.create');
    }

    /**
     * Сохраняет файл на сервере.
     *
     * Вызывает обработку файла и сохраняет информацию
     * в БД. Если при обработке файла были получены модификации или изменена
     * информация об оригинальном файле, то эти данные также сохраняются в БД.
     *
     * @param  FileRequest   $request
     * @return Illuminate\Http\RedirectResponse
     */
    public function store(FileRequest $request)
    {
        $fileinfo = [
            'filename' => $request->file('file')->getClientOriginalName(),
            'title' => pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME),
            'mime' => $request->file('file')->getMimeType(),
            'extension' => $request->file('file')->clientExtension() ?? pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_EXTENSION),
        ];

        // TODO: Директорию (диск) для сохранения можно будет выбирать при загрузке файла из конфигурации filesystems
        $disk = $this->disk;
        $directory = Storage::disk($disk)->getAdapter()->getPathPrefix();

        // Генерируем новое имя файла
        $gename = new GeName();
        $gename->setPattern($this->filenamePattern);
        $gename->setInitialData([
            'mime' => $fileinfo['mime'],
            'extension' => $fileinfo['extension'],
            'filename' => $fileinfo['filename'],
        ]);
        $gename->setDirectory($directory);
        $filename = $gename->generateName();

        // Конфигурация хранится в настройках config/file_handler.php
        $fileHandler = new FileHandler(config('file_handler.handlers'), config('file_handler.rules'), config('file_handler.scripts'));
        $fileHandler->setOriginalFile($request->file('file')->getPathName(), $fileinfo);
        $fileHandler->setDirectory($directory);
        $fileHandler->setHandlingScriptByScriptName('user-device');

        if (! $fileHandler->save($filename)) {
            return redirect()->route('file.store_error_redirect')->with(['alert' => ['status' => 'fileNotSaved', 'file' => $fileinfo]]);
        }

        // Со страницы формы может быть передан измененный вариант обработки скрипта
        // Конфигурация задается из файла config/filehandler.php и настройки
        // задаются вручную. Каждый ключ массива соответствует пакету обработки.
        // Настройки обработки файлов переопределяются или задаются с формы
        // в виде массива с именем handler_parameters.<package>.*, где
        // package - название пакета, принимаемое и обрабатываемое
        // в методе handle.
        $fileHandler->handle($request->input('handler_parameters'));

        // Получаем информацию об оригинальном файле и модификациях,
        // затем сохраняем ее в БД.
        $basicFileinfo = $fileHandler->getBasicFileProperties();
        $additionalFileinfo = $fileHandler->getAdditionalFileProperties();
        $files = $fileHandler->getFilePaths();
        $basicPropertiesModifications = $fileHandler->getBasicProperties();
        $additinalPropertiesModifications = $fileHandler->getAdditionalProperties();

        // Информация для таблицы файлов
        $systemInfo = [
            'disk' => $disk,
            'author_id' => 1,
            'path' => $filename,
        ];

        $file = $this->files->createWithoutGuardedFields(array_merge($fileinfo, $request->input(), $systemInfo, $basicFileinfo, ['options' => $additionalFileinfo]));

        if (! empty($files) && is_array($files)) {

            // Перед передачей для сохранения необходимо подготовить информацию
            // с определенными данными.
            foreach ($files as $fn => $properties) {

                // Добавляем в имя файла ключ родителя, хотя можем использовать
                // все что угодно
                // TODO вынести в отдельный метод и передать туда массив данных?
                $title = $file->title.' ['.$file->id.']';

                $files[$fn] = [
                    'disk' => $disk,
                    'author_id' => 1,
                    'path' => $fn,
                    'title' => $title,
                ];

                $files[$fn] = array_merge($files[$fn], $basicPropertiesModifications[$fn]);
                $files[$fn]['options'] = $additinalPropertiesModifications[$fn];
            }

            $this->files->createModifications($file->id, $files);
        }

        return redirect()->route('file_tools.files.edit', $file->id)->with(['status' => 'saved', 'file' => $file]);
    }

    /**
     * Отображает страницу с файлом для загрузки или отображения, также
     * содержит сведения о модификациях.
     *
     * @param  integer $id
     * @return View
     */
    public function show($id)
    {
        $file = $this->service->getFileWithModifications($id);

        return view('belca-file::files.show', compact('file'));
    }

    /**
     * Отображает форму редактирования данных файла.
     *
     * @param  integer $id
     * @return View
     */
    public function edit($id)
    {
        $file = $this->service->getFile($id, true);

        return view('belca-file::files.edit', compact('file'));
    }

    /**
     * Обновляет информацию о файле.
     * После обновления информации выполняется перенаправление на другую страницу
     * с возвратом статуса о действии.
     *
     * @param  FileRequest   $request
     * @param  integer       $id
     * @return Illuminate\Http\RedirectResponse
     */
    public function update(FileRequest $request, $id)
    {
        $file = $this->service->updateFileInfo($id, $request->input());

        return redirect()->route('file_tools.files.edit', compact('file'))->with([
            'alert' => [
                'action' => 'update',
                'status' => true,
                'id' => $id,
                'data' => $file,
            ],
        ]);
    }

    /**
     * Удаляет файл и его модификации. После завершения удаления файлов
     * перенаправляет на другую страницу и возвращает статус удаления.
     *
     * @param  FileRequest  $request
     * @param  integer      $id
     * @return Illuminate\Http\RedirectResponse
     */
    public function destroy(FileRequest $request, $id)
    {
        $result = $this->service->deleteFileWithModifications($id);

        $info = [
            'alert' => [
                'action' => 'delete',
                'id' => $id,
            ],
        ];

        $info['status'] = ! empty($result);

        if (! empty($result)) {
            $info['data'] = $result;
        }

        $route = empty($result) ? 'file_tools.files.edit' : 'file_tools.files.index';

        return redirect()->route($route)->with($info);
    }
}
