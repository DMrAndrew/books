<?php

namespace Books\Comments\Components;

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
            'description' => 'No description provided yet...',
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
        //TODO EagerLoad
        return $this->user->comments()->with('commentable')->get();
    }

    public function onRemove()
    {
        if ($comment = $this->user->comments()->find(post('id'))) {
            $comment->commentable->deleteComment($comment);
        }

        return ['#comments-spawn' => $this->renderPartial('@default', [
            'comments' => $this->getCommentsList(),
        ])];
    }
}
