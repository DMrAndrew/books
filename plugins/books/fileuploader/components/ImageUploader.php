<?php

namespace Books\FileUploader\Components;

use ApplicationException;
use Books\FileUploader\Traits\ComponentUtils;
use Cms\Classes\ComponentBase;

class ImageUploader extends ComponentBase
{
    use ComponentUtils;

    public $maxSize;

    public $imageWidth;

    public $imageHeight;

    public $imageMode;

    public $previewFluid;

    public $placeholderText;

    /**
     * @var array Options used for generating thumbnails.
     */
    public $thumbOptions = [
        'mode' => 'crop',
        'extension' => 'auto',
    ];

    /**
     * Supported file types.
     *
     * @var array
     */
    public $fileTypes;

    /**
     * @var bool Has the model been bound.
     */
    protected $isBound = false;

    /**
     * @var bool Is the related attribute a "many" type.
     */
    public $isMulti = false;

    /**
     * @var Collection
     */
    public $fileList;

    /**
     * @var Model
     */
    public $singleFile;

    public function componentDetails()
    {
        return [
            'name' => 'books.fileuploader::lang.component.image_uploader',
            'description' => 'books.fileuploader::lang.component.image_uploader_desc',
        ];
    }

    public function defineProperties()
    {
        return [
            'placeholderText' => [
                'title' => 'books.fileuploader::lang.prop.placeholder',
                'description' => 'books.fileuploader::lang.prop.placeholder_img_desc',
                'default' => 'Click or drag images to upload',
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
                'default' => '.gif,.jpg,.jpeg,.png',
                'type' => 'string',
            ],
            'imageWidth' => [
                'title' => 'books.fileuploader::lang.prop.imageWidth',
                'description' => 'books.fileuploader::lang.prop.imageWidth_desc',
                'default' => '100',
                'type' => 'string',
            ],
            'imageHeight' => [
                'title' => 'books.fileuploader::lang.prop.imageHeight',
                'description' => 'books.fileuploader::lang.prop.imageHeight_desc',
                'default' => '100',
                'type' => 'string',
            ],
            'imageMode' => [
                'title' => 'books.fileuploader::lang.prop.imageMode',
                'description' => 'books.fileuploader::lang.prop.imageMode_desc',
                'default' => 'crop',
                'type' => 'string',
            ],
            // 'previewFluid' => [
            //     'title'       => 'books.fileuploader::lang.prop.previewFluid',
            //     'description' => 'books.fileuploader::lang.prop.previewFluid_desc',
            //     'default'     => 0,
            //     'type'        => 'checkbox',
            // ],
            'deferredBinding' => [
                'title' => 'books.fileuploader::lang.prop.deferredBinding',
                'description' => 'books.fileuploader::lang.prop.deferredBinding_desc',
                'type' => 'checkbox',
            ],
        ];
    }

    public function init()
    {
        $this->fileTypes = $this->processFileTypes(true);
        $this->maxSize = $this->property('maxSize');
        $this->imageWidth = $this->property('imageWidth');
        $this->imageHeight = $this->property('imageHeight');
        $this->imageMode = $this->property('imageMode');
        $this->previewFluid = $this->property('previewFluid');
        $this->placeholderText = $this->property('placeholderText');

        $this->thumbOptions['mode'] = $this->imageMode;
    }

    public function onRun()
    {
        $this->addCss(['assets/css/uploader.css']);
        $this->addJs([
            'assets/vendor/dropzone/dropzone.js',
            'assets/js/uploader.js',
        ]);

        $this->autoPopulate();
    }

    public function getCssBlockDimensions()
    {
        return $this->getCssDimensions('block');
    }

    /**
     * Returns the CSS dimensions for the uploaded image,
     * uses auto where no dimension is provided.
     *
     * @param string $mode
     * @return string
     */
    public function getCssDimensions($mode = null)
    {
        if (!$this->imageWidth && !$this->imageHeight) {
            return '';
        }

        $cssDimensions = '';

        if ($mode == 'block') {
            $cssDimensions .= ($this->imageWidth)
                ? 'width: ' . $this->imageWidth . 'px;'
                : 'width: ' . $this->imageHeight . 'px;';

            $cssDimensions .= ($this->imageHeight)
                ? 'height: ' . $this->imageHeight . 'px;'
                : 'height: auto;';
        } else {
            $cssDimensions .= ($this->imageWidth)
                ? 'width: ' . $this->imageWidth . 'px;'
                : 'width: auto;';

            $cssDimensions .= ($this->imageHeight)
                ? 'height: ' . $this->imageHeight . 'px;'
                : 'height: auto;';
        }

        return $cssDimensions;
    }

    /**
     * Adds the bespoke attributes used internally by this widget.
     * - thumbUrl
     * - pathUrl
     *
     * @return System\Models\File
     */
    protected function decorateFileAttributes($file)
    {
        $path = $thumb = $file->getPath();

        if (!empty($this->imageWidth) || !empty($this->imageHeight)) {
            $thumb = $file->getThumb($this->imageWidth, $this->imageHeight, $this->thumbOptions);
        } else {
            $thumb = $file->getThumb(63, 63, $this->thumbOptions);
        }

        $file->pathUrl = $path;
        $file->thumbUrl = $thumb;

        return $file;
    }

    public function onRender()
    {
        if (!$this->isBound) {
            throw new ApplicationException('There is no model bound to the uploader!');
        }

        if ($populated = $this->property('populated')) {
            $this->setPopulated($populated);
        } else {
            $this->autoPopulate();
        }
    }
}
