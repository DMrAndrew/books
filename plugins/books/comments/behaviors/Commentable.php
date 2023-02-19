<?php

namespace Books\Comments\behaviors;

use Books\Comments\Models\Comment;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class Commentable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphToMany['comments'] = [Comment::class, 'name' => 'commentable'];
    }

    public function addComment(User $user, array $payload)
    {
        $payload['user_id'] = $user->id;
        return $this->model->comments()->add(new Comment($payload));
    }

    public function replay(Comment $comment, User $user, array $payload)
    {
        $payload['parent_id'] = $comment->id;
        return $this->model->addComment($user, $payload);
    }

    public function deleteComment(Comment $comment)
    {
        $comment->delete();
    }
}
