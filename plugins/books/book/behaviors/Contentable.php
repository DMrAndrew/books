<?php

namespace Books\Book\Behaviors;

use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;

class Contentable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphOne['content'] = [Content::class, 'name' => 'contentable', 'scope' => 'regular'];
        $this->model->morphMany['deferredContent'] = [Content::class, 'name' => 'contentable', 'scope' => 'deferred'];
        $this->model->morphOne['deferredContentOpened'] = [Content::class, 'name' => 'contentable', 'scope' => 'deferredOpened'];
        $this->model->bindEvent('model.afterCreate', fn() => $this->afterCreate());
        $this->model->bindEvent('model.afterSave', fn() => $this->afterSave());
    }

    public function afterCreate()
    {
        if (!$this->model->content()->exists()) {
            $this->model->content()->create();
        }
    }

    public function afterSave()
    {

        if ($content = $this->model->getOriginalPurgeValue('new_content')) {
            $this->model->content->fill(['body' => $content]);
            $this->model->content->save();
            if ($this->model->content->wasChanged(['body'])) {
                if ($this->model instanceof Chapter) {
                    $this->model->paginateContent();
                }
            }
        }

        if ($content = $this->model->getOriginalPurgeValue('deferred_content')) {

            $this->model->deferredContent()->deferredOpened()->updateOrCreate(['type' => ContentTypeEnum::DEFERRED->value], ['body' => $content]);
        }
    }
}
