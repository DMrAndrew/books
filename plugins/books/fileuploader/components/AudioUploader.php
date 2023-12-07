<?php namespace Books\FileUploader\Components;

use ApplicationException;
use Books\FileUploader\Traits\ComponentUtils;
use Cms\Classes\ComponentBase;
use Exception;
use Input;
use October\Rain\Database\Model;
use October\Rain\Support\Collection;
use Response;
use ValidationException;
use Validator;


class AudioUploader extends ComponentBase
{
    use ComponentUtils;

    /**
     * @var array fileTypes supported
     */
    public $fileTypes;

    /**
     * @var bool isBound determines if the model been bound.
     */
    protected $isBound = false;

    /**
     * @var bool isMulti true if the related attribute a "many" type.
     */
    public $isMulti = false;

    /**
     * @var Collection fileList
     */
    public $fileList;

    /**
     * @var Model singleFile
     */
    public $singleFile;

    /**
     * @var string|int maxSize
     */
    public $maxSize;

    /**
     * @var string placeholderText
     */
    public $placeholderText;

    public function componentDetails()
    {
        return [
            'name' => 'books.fileuploader::lang.component.file_uploader',
            'description' => 'books.fileuploader::lang.component.file_uploader_desc'
        ];
    }

    public function defineProperties()
    {
        return [
            'placeholderText' => [
                'title' => 'books.fileuploader::lang.prop.placeholder',
                'description' => 'books.fileuploader::lang.prop.placeholder_file_desc',
                'default' => 'Нажмите или перетяние файл для загрузки',
                'type' => 'string',
            ],
            'maxSize' => [
                'title' => 'books.fileuploader::lang.prop.maxSize',
                'description' => 'books.fileuploader::lang.prop.maxSize_desc',
                'default' => '5',
                'type' => 'string',
            ],
            'fileTypes' => [
                'title' => 'books.fileuploader::lang.prop.fileTypes',
                'description' => 'books.fileuploader::lang.prop.fileTypes_desc',
                'default' => '*',
                'type' => 'string',
            ],
            'deferredBinding' => [
                'title' => 'books.fileuploader::lang.prop.deferredBinding',
                'description' => 'books.fileuploader::lang.prop.deferredBinding_desc',
                'type' => 'checkbox',
            ],
        ];
    }

    /**
     * init
     */
    public function init()
    {
        $this->fileTypes = $this->processFileTypes(true);
        $this->maxSize = $this->property('maxSize');
        $this->placeholderText = $this->property('placeholderText');
    }

    /**
     * onRun
     */
    public function onRun()
    {
        $this->addCss(['assets/css/uploader-audio.css']);
        $this->addJs([
            'assets/vendor/dropzone/dropzone.js',
            'assets/js/uploader-audio.js',
        ]);

        $this->autoPopulate();
    }

    /**
     * onRender
     */
    public function onRender()
    {
        if (!$this->isBound) {
            throw new ApplicationException('There is no model bound to the uploader!');
        }

        if ($populated = $this->property('populated')) {
            $this->setPopulated($populated);
        }
        else {
            $this->autoPopulate();
        }
    }

    /**
     * decorateFileAttributes adds the bespoke attributes used internally by this widget.
     * - thumbUrl
     * - pathUrl
     * @return System\Models\File
     */
    protected function decorateFileAttributes($file)
    {
        $file->pathUrl = $file->thumbUrl = $file->getPath();

        return $file;
    }

    public function onUpload()
    {
        try {
            if (!Input::hasFile('file_data')) {
                throw new ApplicationException('File missing from request');
            }

            $uploadedFile = Input::file('file_data');


            $validationRules = ['max:' . $this->getMaxFileSize()];
            if ($fileTypes = $this->processFileTypes()) {
                $validationRules[] = 'extensions:' . $fileTypes;
            }

            // Support model validation rules
            if (!empty($this->model->rules[$this->attribute])) {
                $rules = $this->model->rules[$this->attribute];
                if (is_string($rules)) {
                    $validationRules = $validationRules + explode('|', $this->model->rules[$this->attribute]);
                }
                if (is_array($rules)) {
                    $validationRules = $validationRules + $rules;
                }
            }

            $validation = Validator::make(
                ['file_data' => $uploadedFile],
                ['file_data' => $validationRules],
                $this->model->customMessages ?? []
            );

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new ApplicationException(sprintf('File %s is not valid.', $uploadedFile->getClientOriginalName()));
            }

            $relationDefinition = $this->model->getRelationDefinition($this->attribute);
            $fileModel = $relationDefinition[0];
            $isPublic = array_get($relationDefinition, 'public', true);

            $file = new $fileModel;
            $file->data = $uploadedFile;
            $file->is_public = $isPublic;
            $file->save();

            $this->model->{$this->attribute}()->add($file, $this->getSessionKey());

            $file = $this->decorateFileAttributes($file);

            $result = [
                'id' => $file->id,
                'path' => $file->pathUrl
            ];

            return Response::json($result, 200);
        }
        catch (Exception $ex) {
            return Response::json($ex->getMessage(), 400);
        }
    }

    /**
     * onRemoveAttachment removes an attachment
     */
    public function onRemoveAttachment()
    {
        if (!$fileId = post('file_id')) {
            return;
        }

        if (
            method_exists($this->model, 'shouldDeferredUpdate')
            && $this->model->shouldDeferredUpdate()
            && method_exists($this->model, 'saveAsDraft')
        ) {
            // Отложенное редактирование
            $this->createDraftWithAttachment();
        } else {
            $this->removeAttachment();
        }
    }

    private function removeAttachment(): void
    {
        if (!$fileId = post('file_id')) {
            return;
        }

        /*
         * Use deferred bindings
         */
        if ($sessionKey = $this->getSessionKey()) {
            $file = $this->model
                ->{$this->attribute}()
                ->withDeferred($sessionKey)
                ->find($fileId);
        }
        else {
            $file = $this->model
                ->{$this->attribute}()
                ->find($fileId);
        }

        if ($file) {
            $this->model->{$this->attribute}()->remove($file, $this->getSessionKey());
        }
    }

    private function createDraftWithAttachment(): void
    {
        $this->model->saveAsDraft(sessionKey: $this->getSessionKey());
        $this->model->setCurrent();
        $this->model->saveQuietly();
    }
}
