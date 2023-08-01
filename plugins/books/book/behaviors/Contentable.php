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
        $this->model->morphOne['deletedContent'] = [Content::class, 'name' => 'contentable', 'scope' => 'onDeleteOpened'];
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
            if ($this->model->content->isDirty('body')) {
                $this->model->content->save();
                if ($this->model instanceof Chapter) {
                    $this->model->paginateContent();
                }
            }
        }

        if ($content = $this->model->getOriginalPurgeValue('deferred_content')) {

            if ($deferred = $this->model->deferredContentOpened) {
                $deferred->fill(['body' => $content]);
                if ($deferred->isDirty('body')) {
                    $deferred->save();
                }
            } else {
                if (strcasecmp($content, $this->model->content->body) === 0) {
                    return;
                }

                $this->model->deferredContentOpened()->create([
                    'type' => ContentTypeEnum::DEFERRED_UPDATE->value,
                    'body' => $content
                ]);
            }
        }
    }
}
