<?php namespace Books\Comments\Components;

use Books\Comments\behaviors\Commentable;
use Closure;
use Cms\Classes\ComponentBase;
use Exception;
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
        $this->page['user'] = $this->user;
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
        $this->prepareVals();
    }

    public function onComment()
    {
        if (!$this->user) {
            return;
        }
        $payload = post();
        $parent = $this->model->comments()->find(post('parent_id'));
        $comment = null;
        if ($parent) {
            $this->model->reply($parent, $this->user, $payload);
            $comment = $parent;
        } else {
            $comment = $this->model->addComment($this->user, $payload);
        }
        $comment = $this->model->comments()->with(['profile:id,avatar,username','children'])->find($comment->id);

    }
}
