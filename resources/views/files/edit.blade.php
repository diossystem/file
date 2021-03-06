{{-- TODO Должен иметь возможность расширения c помощью секций, компонентов и стеков --}}
@extends(component_path('app'))

@section('title', 'Правка файла: '.$file->title.' ['.$file->id.']')

@section('content')
  @component(component_path('workspace'))
    {{-- Breadcrumbs --}}
    @include(component_path('breadcrumbs'), [
      'breadcrumbs' => [
        ['link' => '/system', 'label' => 'Главная'],
        ['link' => route('file_tools.files.index'), 'label' => 'Файлы'],
        ['label' => 'Правка файла']
      ]
    ])

    {{-- Container --}}
    @component(component_path('container'))

      {{-- Heading --}}
      @component(component_path('heading'))
        Правка файла: {{ $file->title }}
      @endcomponent

      {{-- Delete form  --}}
      @component(component_path('form'), ['crud' => 'delete','attributes' => ['action' => route('file_tools.files.destroy', $file->id), 'id' => 'deleteForm']])
      @endcomponent

      {{-- Form --}}
      @component(component_path('form'), ['crud' => 'put','attributes' => ['action' => route('file_tools.files.update', $file->id)]])

        {{-- Control panel --}}
        @component(component_path('control_panel'))
          @component(component_path('button'), ['type' => 'primary', 'attributes' => ['type' => 'submit']])
            Сохранить
          @endcomponent

          @component(component_path('link_button'), ['attributes' => ['href' => route('file_tools.files.show', $file->id), 'target' => '_blank']])
            Открыть файл
          @endcomponent

          @component(component_path('button'), ['type' => 'danger', 'attributes' => ['form' => 'deleteForm', 'type' => 'submit', 'id' => 'delete']])
            Удалить
          @endcomponent

          @component(component_path('link_button'), ['attributes' => ['href' => route('file_tools.files.index')]])
            Назад
          @endcomponent

          {{-- TODO рашсиряется / заменяется через section и внутренние элементы --}}
        @endcomponent

        {{-- Alerts --}}
        @include(config('file.alerts_component'))

        {{-- Zones --}}
        @component(component_path('zones'))
          @component(component_path('zone'), ['type' => 'basic'])
            {{-- Groups --}}
            @component(component_path('groups'))

              {{-- Group - Main --}}
              @component(component_path('group'))
                @component(component_path('heading'), ['type' => 'h2'])
                  @lang('belca-file::files.file_information')
                @endcomponent

                {{-- Input - Title --}}
                @component(component_path('input_field'), ['attributes' => ['name' => 'title', 'value' => $file->title, 'placeholder' => __('belca-file::files.filename')]])
                  @slot('label')
                    @lang('belca-file::files.filename')
                  @endslot
                @endcomponent

                {{-- TODO дополняется, например, категория, диск --}}

                {{-- Textarea - Description --}}
                @component(component_path('textarea_field'), ['attributes' => ['name' => 'description', 'placeholder' => __('belca-file::files.file_description')]])
                  @slot('label')
                    @lang('belca-file::files.file_description')
                  @endslot

                  {{ $file->description ?? '' }}
                @endcomponent
              @endcomponent

              {{-- Group - Published --}}
              @component(component_path('group'))
                @component(component_path('heading'), ['type' => 'h2'])
                  Публикация файла
                @endcomponent

                @component(component_path('description'))
                  <p>
                    Загружаемые файлы можно скачать без публикации. Но каждому
                    загружаемому файлу присваивается уникальное труднозапоминаемое имя.
                  </p>
                  <p>
                    Опубликованный файл можно скачать по уникальной ссылке, которую
                    можно запонить. На разные файлы можно указать одинаковую ссылку, но
                    доступна для скачивания будет только одна.
                  </p>
                @endcomponent

                @component(component_path('checkbox_field'), ['attributes' => ['name' => 'published', 'checked' => $file->published ?? false]])
                  Файл разрешен для скачивания по прямой ссылке
                @endcomponent

                {{-- Input - Slug --}}
                @component(component_path('linkinput'), ['receivers' => ['#linkLink', '#link'], 'prefix_link' => route('file_tools.download', ''), 'attributes' => ['name' => 'slug', 'placeholder' => 'ЧПУ файла', 'value' => $file->slug ?? '']])
                  @slot('label')
                    ЧПУ
                  @endslot
                @endcomponent

                @component(component_path('linkoutput'), ['open' => true, 'copy' => true, 'prefix_link' => route('file_tools.download', ''), 'attributes' => ['id' => 'link', 'placeholder' => 'Ссылка для скачивания файла', 'value' => $file->slug ?? '']])
                  @slot('label')
                    Ссылка на файл
                  @endslot
                  @slot('note')
                    (заполняется при вводе ЧПУ)
                  @endslot
                @endcomponent

                {{-- TODO добавление элементов или полная замена секции, например, добавление кнопки поделиться в соцсетях или через почту --}}
              @endcomponent

              {{-- TODO расширяется секция --}}
              {{-- Group - Storage --}}
              @component(component_path('group'))
                @component(component_path('heading'), ['type' => 'h2', 'attributes' => ['class' => 'f']])
                  Хранение файла
                @endcomponent

                @component(component_path('description'))
                  <p>
                    Загружаемые файлы можно скачать без публикации. Но каждому
                    загружаемому файлу присваивается уникальное труднозапоминаемое имя.
                  </p>
                  <p>
                    Опубликованный файл можно скачать по уникальной ссылке, которую
                    можно запонить. На разные файлы можно указать одинаковую ссылку, но
                    доступна для скачивания будет только одна.
                  </p>
                @endcomponent

                {{-- Input - Direct file link --}}
                @component(component_path('linkoutput'), ['open' => true, 'copy' => true, 'attributes' => ['value' => \Storage::url($file->path), 'placeholder' => 'Прямая ссылка для загрузки файла', 'readonly' => true, 'id' => 'filepath']])
                  @slot('label')
                    Прямая ссылка на оригинальный файл
                  @endslot
                @endcomponent

                {{-- Disk / Storage --}}
                @component(component_path('staticvalue'))
                  @slot('label')
                    Путь к хранилищу (<b>{{ $file->disk }}</b>)
                  @endslot

                  {{ \Storage::disk($file->disk)->getAdapter()->getPathPrefix() }}
                @endcomponent

                {{-- Input - Relative file path --}}
                @component(component_path('linkoutput'), ['copy' => true, 'attributes' => ['value' => $file->path, 'placeholder' => 'Путь к файлу относительно хранилища', 'readonly' => true]])
                  @slot('label')
                    Путь к файлу относительно хранилища
                  @endslot
                @endcomponent
              @endcomponent
            @endcomponent
          @endcomponent

          @component(component_path('zone'), ['type' => 'additional', 'class' => 'padding_left_small@s'])
            @component(component_path('groups'))

              {{-- Group - Additional --}}
              @component(component_path('group'))
                @component(component_path('heading'), ['type' => 'h2'])
                  Дополнительные сведения
                @endcomponent

                {{-- Cover --}}
                @if (in_array($file->mime, config('file.supported_thumbnails')))
                  @component(component_path('file_cover'), ['attributes' => ['src' => \Storage::url($file->path)]])
                    @slot('label')
                      Миниатюра файла
                    @endslot
                  @endcomponent
                @endif

                {{-- Upload date --}}
                @component(component_path('staticvalue'))
                  @slot('label')
                    Дата загрузки
                  @endslot

                  {{ $file->created_at }}
                @endcomponent

                {{-- User --}}
                @isset($file->user_id)
                  @component(component_path('staticvalue'))
                    @slot('label')
                      Пользователь
                    @endslot

                    {{ $file->user->full_name }}
                  @endcomponent
                @endisset

                {{-- Extension --}}
                @isset($file->extension)
                  @component(component_path('staticvalue'))
                    @slot('label')
                      Расширение
                    @endslot

                    {{ $file->extension }}
                  @endcomponent
                @endisset

                {{-- MIME --}}
                @component(component_path('staticvalue'))
                  @slot('label')
                    Тип MIME
                  @endslot

                  {{ $file->mime }}
                @endcomponent

                {{-- Modifications --}}
                @component(component_path('staticvalue'))
                  @slot('label')
                    Модификаций:
                  @endslot

                  {{ $file->modifications()->count() }}
                @endcomponent
              @endcomponent

            @endcomponent
          @endcomponent
        @endcomponent

    @endcomponent

    @endcomponent
  @endcomponent

  {{-- Config file deletion --}}
  @if (config('file.confirm_file_deletion'))
    @push('footer')
      <script type="text/javascript">
        UIkit.util.on('#delete', 'click', function (e) {
             e.preventDefault();
             e.target.blur();
             UIkit.modal.confirm('Вы действительно хотите удалить файл? Вместе с файлом будут удалены его модификации. Если файл и/или его модификации используются на сайте, то их удаление может привести к нерабочим ссылкам и ошибкам загрузки данных! Перед удалением файла убедитесь что файл и его модификации нигде не используется.').then(function () {
                 deleteForm.submit();
             });
         });
      </script>
    @endpush
  @endif
@endsection
