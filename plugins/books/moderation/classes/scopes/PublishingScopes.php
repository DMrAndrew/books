<?php

namespace Books\Moderation\Classes\Scopes;

use Books\Moderation\Classes\PremoderationDrafts;
use October\Rain\Database\Builder;
use October\Rain\Database\Model;

trait PublishingScopes
{
    public function apply(Builder $builder, Model $model): void
    {
        $moderationDraft = app()->make(PremoderationDrafts::class);
        if ($moderationDraft->isPreviewModeEnabled() || $moderationDraft->isWithDraftsEnabled()) {
            return;
        }
        $builder->where($model->getQualifiedIsPublishedColumn(), 1);
    }

    public function scopeModerationPublished(Builder $builder, $withoutDrafts = true): Builder
    {
        return $builder->withDrafts(! $withoutDrafts);
    }

    public function scopeWithDrafts(Builder $builder, $withDrafts = true): Builder
    {
        if (! $withDrafts) {
            return $builder->withoutDrafts();
        }

        return $builder->withoutGlobalScope($this);
    }

    public function scopeWithoutDrafts(Builder $builder): Builder
    {
        $model = $builder->getModel();

        $builder->withoutGlobalScope($this)
            ->where($model->getQualifiedIsPublishedColumn(), 1);

        return $builder;
    }

    public function scopeOnlyDrafts(Builder $builder): Builder
    {
        $model = $builder->getModel();

        $builder->withoutGlobalScope($this)
            ->where($model->getQualifiedIsPublishedColumn(), 0);

        return $builder;
    }
}
