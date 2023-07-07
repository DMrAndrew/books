<?php

namespace Books\Comments\behaviors;

use Books\Book\Models\Book;
use Books\Comments\Models\Comment;
use Books\Profile\Models\Profile;
use Closure;
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

    public function isCommentAllowed(): bool
    {
        return true;
    }

    public function addComment(User $user, array $payload)
    {
        if (!$this->model->isCommentAllowed()) {
            return false;
        }
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
