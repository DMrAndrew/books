<?php

namespace Books\Comments\behaviors;

use Books\Book\Models\Book;
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
        $comment = $this->model->comments()->create($payload);
        $this->after($comment);

        return $comment;
    }

    public function deleteComment(Comment $comment)
    {
        $comment->delete();
        $this->after($comment);
    }

    protected function after($comment)
    {
        if ($comment->commentable instanceof Book) {
            $comment->commentable->rater()->comments()->queue();
        }
    }

    public function scopeCommentsCount(Builder $builder): Builder
    {
        return $builder->withCount('comments');
    }
}
