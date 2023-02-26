<?php namespace Books\Comments\Components;

use Exception;
use App\classes\PomonPaginator;
use Books\Comments\behaviors\Commentable;
use Books\Comments\Models\Comment;
use Books\Profile\Models\Profile;
use Closure;
use Cms\Classes\ComponentBase;
use October\Rain\Database\Model;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Comments Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Comments extends ComponentBase
{
    protected Model $model;
    protected ?User $user;
    protected Profile $owner;
    protected int $perPage = 5;
    protected int $currentPage = 1;

    public function componentDetails()
    {
        return [
            'name' => 'Comments Component',
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

    public function prepareVals()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }


        $this->page['comments_allowed'] = $this->model->isCommentAllowed();
        if ($this->model->isCommentAllowed()) {
            $this->page['comments_count'] = $this->queryComments()->count();
            $all = $this->queryComments()->get()->toNested();
            $items = $all->forPage($this->currentPage(), $this->perPage);
            $this->page['paginator'] = new PomonPaginator($items, $all->count(), $this->perPage, $this->currentPage());
            $this->page['current_page'] = $this->currentPage();

        }
    }

    public function vals(): array
    {
        return [
            'user' => $this->user,
            'owner' => $this->owner
        ];

    }

    public function queryComments()
    {
        return $this->model->comments()->with(['profile', 'profile.avatar', 'children']);
    }

    /**
     * @throws Exception
     */
    public function bindModel(Closure|Model $model)
    {
        if (is_callable($model)) {
            $model = $model();
        }

        $this->model = $model;

        if (!$this->model->isClassExtendedWith(Commentable::class)) {
            throw new Exception(get_class($this->model) . ' must be extended with ' . Commentable::class . ' behavior.');
        }
    }

    public function bindModelOwner(Closure|Profile $model)
    {
        if (is_callable($model)) {
            $model = $model();
        }
        $this->owner = $model;
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    public function onPage()
    {
        $this->prepareVals();
        $this->currentPage = post('page');
        return $this->render();
    }

    public function onComment()
    {
        if (!$this->user) {
            return;
        }
        $payload = post();
        if (!$this->model->comments()->find(post('parent_id'))) {
            unset($payload['parent_id']);
        }
        $comment = $this->model->addComment($this->user, $payload);
        return $this->render();
    }

    public function onEdit()
    {
        if (!$this->user) {
            return;
        }

        $comment = $this->queryComments()->find(post('comment_id'));
        if (!$this->validateComment($comment)) {
            return;
        }
        $comment->update(['content' => post('content')]);
        return $this->render();
    }

    public function onRemove()
    {
        if (!$this->user) {
            return;
        }

        $comment = $this->queryComments()->find(post('id'));
        if (!$this->validateComment($comment)) {
            return;
        }
        $this->model->deleteComment($comment);
        return $this->render();

    }

    public function validateComment(?Comment $comment): bool
    {
        return $comment && ($this->user?->profile->is($this->owner) || $comment->profile?->is($this->user?->profile));
    }

    public function render()
    {
        $this->prepareVals();
        return [
            '.comments-spawn' => $this->renderPartial('@default')
        ];
    }

    public function currentPage(): int
    {
        return (int)(post('page') ?? $this->currentPage);
    }
}
