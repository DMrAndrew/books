<?php

namespace Books\Comments\behaviors;

use Books\Comments\Models\Comment;
use October\Rain\Database\Builder;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class Commentable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['comments'] = [Comment::class, 'name' => 'commentable'];
    }

    public function addComment(User $user, array $payload)
    {
        $payload['user_id'] = $user->id;
        return $this->model->comments()->create($payload);
    }

    public function deleteComment(Comment $comment)
    {
        $comment->delete();
    }

    public function scopeCommentsCount(Builder $builder): Builder
    {
        return $builder->withCount('comments');
    }
}
