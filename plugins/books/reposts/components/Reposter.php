<?php namespace Books\Reposts\Components;

use ApplicationException;
use Books\Book\Models\Book;
use Books\Reposts\behaviors\Shareable;
use Cms\Classes\ComponentBase;
use October\Rain\Database\Model;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Reposter Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Reposter extends ComponentBase
{
    protected User $user;
    protected Model $model;

    public function componentDetails()
    {
        return [
            'name' => 'Reposter Component',
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
        if ($r = redirectIfUnauthorized()) {
            return $r;
        }
        $this->user = Auth::getUser();
    }

    public function onRender()
    {
        $this->getVals();
    }

    public function getVals()
    {
        $this->page['reposts'] = $this->getReposts();
        $this->page['repostIsAllowed'] = $this->repostIsAllowed();
    }

    public function repostIsAllowed()
    {
        return match (get_class($this->model)) {
            Book::class => Book::query()->public()->find($this->model->id),
            default => false
        };
    }

    public function getReposts()
    {
        return $this->model?->reposts()->with(['profile'])->get() ?? collect();
    }

    public function bindSharable(Model $model): void
    {
        if (!$model->isClassExtendedWith(Shareable::class)) {
            throw new ApplicationException(get_class($model) . ' must be extended by ' . Shareable::class);
        }
        $this->model = $model;
    }

    public function onRepost()
    {
        if ($this->user) {
            $this->model->reposted($this->user);
            return $this->render();
        }
    }

    public function render()
    {
        return [
            '#reposts' => $this->renderPartial('@list', ['reposts' => $this->getReposts(), 'repostIsAllowed' => $this->repostIsAllowed()])
        ];
    }

}
