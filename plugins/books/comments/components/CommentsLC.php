<?php namespace Books\Comments\Components;

use Books\Book\Models\Book;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * CommentsLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CommentsLC extends ComponentBase
{

    protected User $user;

    public function componentDetails()
    {
        return [
            'name' => 'CommentsLC Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->user = Auth::getUser();
    }

    public function onRender()
    {
        $this->page['comments'] = $this->getCommentsList();
    }

    public function getCommentsList()
    {
        return $this->user->comments()->with('commentable')->get()->map(function ($comment) {
            $comment['label'] = match ($comment['commentable_type']) {
                Book::class => 'к книге ' . $comment->commentable->title,
                Profile::class => 'в профиле пользователя ' . $comment->commentable->username,
            };
            $comment['link'] = match ($comment['commentable_type']) {
                Book::class => '/book-card/' . $comment->commentable->id,
                Profile::class => '/author-page/' . $comment->commentable->id,
            };

            return $comment;
        });
    }

    public function onRemove()
    {
        if ($comment = $this->user->comments()->find(post('id'))) {
            $comment->commentable->deleteComment($comment);
        }
        return ['#comments-spawn' => $this->renderPartial('@default',[
            'comments' => $this->getCommentsList()
        ])];
    }
}
