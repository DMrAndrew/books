<?php

namespace Books\Comments\behaviors;

use Books\Book\Models\Book;
use Books\Comments\Models\Comment;
use Books\Profile\Models\Profile;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;
use ValidationException;

class Commentable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['comments'] = [Comment::class, 'name' => 'commentable'];
    }

    public function isCommentAllowed(): bool
    {
        return true;
    }

    public function addComment(User $user, array $payload)
    {
        if (! $this->model->isCommentAllowed()) {
            return false;
        }
        $payload['user_id'] = $user->id;
        $comment = $this->model->comments()->create($payload);
        $this->after($comment);

        return $comment;
    }

    public function deleteComment(Comment $comment, ?Profile $actionBy): void
    {
        if (! is_null($actionBy)) {
            $comment->deleted_by_id = $actionBy->id;
            $comment->save();
        }
        $comment->delete();
        $this->after($comment);
    }

    public function restoreComment(Comment $comment, ?Profile $actionBy): void
    {
        if (! $comment->isDeleted()) {
            throw new ValidationException(['comment' => 'Комментарий не может быть восстановлен']);
        }

        if ($comment->deletedBy && ! $comment->deletedBy->is($actionBy)) {
            throw new ValidationException(['comment' => 'Вы не можете восстановить этот комментарий']);
        }
        $comment->restore();
    }

    protected function after($comment)
    {
        if ($comment->commentable instanceof Book) {
            $comment->commentable->rater()->comments()->queue();
        }
    }

    //    public function scopeWithoutOwner(Builder $builder)
    //    {
    //        $profile = match (get_class($this->model)) {
    //            Book::class => $this->model->profile()->select((new Profile())->getQualifiedKeyName()),
    //            Profile::class => [$this->model->id],
    //            default => null
    //        };
    //
    //        return $builder->when($profile, fn($b) => $b->whereDoesntHave('profile', fn($p) => $p->whereIn((new Profile())->getQualifiedKeyName(), $profile)));
    //
    //    }

}
