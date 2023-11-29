<?php

namespace Books\Moderation\Classes\Scopes;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Books\Moderation\Facades\LaravelDrafts;

class PublishingScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = [/*'Publish', 'Unpublish', 'Schedule', */'Published', 'WithDrafts', 'WithoutDrafts', 'OnlyDrafts'];

    public function apply(Builder $builder, Model $model): void
    {
        if (LaravelDrafts::isPreviewModeEnabled() || LaravelDrafts::isWithDraftsEnabled()) {
            return;
        }
        $builder->where($model->getQualifiedIsPublishedColumn(), 1);
    }

    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    protected function addPublished(Builder $builder): void
    {
        $builder->macro('published', function (Builder $builder, $withoutDrafts = true) {
            return $builder->withDrafts(! $withoutDrafts);
        });
    }

    protected function addWithDrafts(Builder $builder): void
    {
        $builder->macro('withDrafts', function (Builder $builder, $withDrafts = true) {
            if (! $withDrafts) {
                return $builder->withoutDrafts();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    protected function addWithoutDrafts(Builder $builder): void
    {
        $builder->macro('withoutDrafts', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedIsPublishedColumn(), 1);

            return $builder;
        });
    }

    protected function addOnlyDrafts(Builder $builder): void
    {
        $builder->macro('onlyDrafts', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedIsPublishedColumn(), 0);

            return $builder;
        });
    }
}
