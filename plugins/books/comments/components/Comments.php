<?php

namespace Books\Comments\Components;

use App\classes\CustomPaginator;
use Books\Comments\behaviors\Commentable;
use Books\Comments\Models\Comment;
use Books\Profile\Models\Profile;
use Closure;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Log;
use October\Rain\Database\Model;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Throwable;
use ValidationException;

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

    protected int $perPage = 16;

    protected int $currentPage = 1;

    public function componentDetails()
    {
        return [
            'name' => 'Comments Component',
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
            $paginator = new CustomPaginator($items, $all->count(), $this->perPage, $this->currentPage());
            $paginator->setHandler($this->alias.'::onPage')->setScrollToContainer('.comments');
            $this->page['paginator'] = $paginator;
            $this->page['current_page'] = $this->currentPage();
        }
        $this->page['opened'] = (array) post('opened');
    }

    public function vals(): array
    {
        return [
            'user' => $this->user,
            'owner' => $this->owner,
        ];
    }

    public function queryComments()
    {
        return $this->model->comments()->withTrashed()->with(['profile', 'profile.avatar', 'children', 'parent.profile']);
    }

    /**
     * @throws Exception
     */
    public function bindModel(Closure|Model $model): void
    {
        $this->model = is_callable($model) ? $model() : $model;

        if (! $this->model->isClassExtendedWith(Commentable::class)) {
            throw new Exception(sprintf('%s must be extended with %s behavior', get_class($this->model), Commentable::class));
        }
    }

    public function bindModelOwner(Closure|Profile $model): void
    {
        $this->owner = is_callable($model) ? $model() : $model;
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    public function onPage()
    {
        $this->prepareVals();
        $this->currentPage = post('page');

        return $this->renderSpawn();
    }

    public function onComment()
    {
        try {
            if (! $this->user) {
                return;
            }

            /**
             * Check if blacklisted
             */
            if ($this->user->profile->isCommentsBlacklistedBy($this->owner)) {
                Flash::error('Автор ограничил вам доступ к публикации комментариев');

                return [];
            }

            if (! post('content')) {
                throw new ValidationException(['content' => 'Введите текст комментария']);
            }
            $payload = post();
            if (! $this->queryComments()->find(post('parent_id'))) {
                unset($payload['parent_id']);
            }
            $this->model->addComment($this->user, $payload);

            return $this->renderSpawn();
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
            if ($exception instanceof ValidationException) {
                Flash::error($exception->getMessage());
            }

            return [];
        }
    }

    public function onEdit()
    {
        if (! $this->user) {
            return;
        }

        $comment = $this->queryComments()->find(post('comment_id'));
        if (! $this->validateComment($comment)) {
            return;
        }
        $comment->update(['content' => post('content')]);

        return $this->renderSpawn();
    }

    public function onRestore()
    {
        if (! $this->user) {
            return;
        }

        $comment = $this->queryComments()->find(post('id'));
        if (! $this->validateComment($comment)) {
            return;
        }
        $comment->restore();

        return $this->renderSpawn();
    }

    public function onRemove()
    {
        if (! $this->user) {
            return;
        }

        $comment = $this->queryComments()->find(post('id'));
        if (! $this->validateComment($comment)) {
            return;
        }
        $this->model->deleteComment($comment);

        return $this->renderSpawn();
    }

    public function validateComment(?Comment $comment): bool
    {
        return $comment && ($this->user?->profile->is($this->owner) || $comment->profile?->is($this->user?->profile));
    }

    public function renderSpawn()
    {
        $this->prepareVals();

        return [
            '.comments-spawn' => $this->renderPartial('@default'),
        ];
    }

    public function currentPage(): int
    {
        return (int) (post('page') ?? $this->currentPage);
    }
}
