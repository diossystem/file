<?php

namespace Belca\File\Http\Requests;

use Belca\File\Contracts\FileRequestInterface;
use Illuminate\Foundation\Http\FormRequest;

class FileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // TODO проверка на возможность загрузки/правки изображений
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // TODO правила проверки изображений. Часть данных берется из настроек и определяется автоматом
        switch ($this->method()) {
            case 'GET':
            case 'DELETE': {
                return [];
            }
            case 'POST': {
                return [
                    'file' => 'required|file', // TODO размер файла и формат файла
                    'title' => 'required|string'
                    // TODO проверка на используемые диски. Диски из конфига, если заданы
                    //
                ];
            }
            case 'PUT':
            case 'PATCH': {
                return [
                    'title' => 'required|string'
                ];
            }
            default:
                break;
        }
    }

    public function messages()
    {
        return trans('belca-file::validation');
    }
}
