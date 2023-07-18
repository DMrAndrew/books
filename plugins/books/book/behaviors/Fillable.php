<?php

namespace Books\Book\Behaviors;

use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;

class Fillable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphOne['content'] = [Content::class, 'name' => 'fillable', 'scope' => 'regular'];
        $this->model->morphOne['deferredContent'] = [Content::class, 'name' => 'fillable', 'scope' => 'deferred'];
        $this->model->bindEvent('model.afterCreate', fn () => $this->afterCreate());
        $this->model->bindEvent('model.afterSave', fn () => $this->afterSave());
    }

    public function afterCreate()
    {
        if (! $this->model->content()->exists()) {
            $this->model->content()->create();
        }
    }

    public function afterSave()
    {
        if ($content = $this->model->getOriginalPurgeValue('new_content')) {
            $this->model->content->fill(['body' => $content]);

            if ($this->model->content->isDirty(['body'])) {
                if ($this->model instanceof Chapter && $this->model->edition->shouldDeferredUpdate()) {
                    $this->model->deferredContent()->updateOrCreate(['type' => ContentTypeEnum::DEFERRED->value], ['body' => $content]);

                    return;
                }
                $this->model->content->save();

                if ($this->model instanceof Chapter) {
                    $this->model->paginateContent();
                }
            }
        }
    }
}
