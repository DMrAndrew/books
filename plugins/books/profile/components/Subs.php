<?php namespace Books\Profile\Components;


use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;

/**
 * Subs Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Subs extends ComponentBase
{

    protected \Books\Profile\Models\Profile $profile;
    protected string $relation;

    public function componentDetails()
    {
        return [
            'name' => 'Subs Component',
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
        $this->profile = Auth::getUser()->profile;
    }

    public function onRender()
    {
        foreach ($this->getVals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    public function options()
    {
        return [
            'subscribers' => [
                'label' => 'Подписаться',
                'request' => $this->alias . '::onAdd',
            ],
            'subscriptions' => [
                'label' => 'Отписаться',
                'request' => $this->alias . '::onRemove',
            ],
        ];
    }

    public function getVals()
    {
        return [
            'items' => $this->profile->{$this->relation}()->get(),
            'options' => $this->options()[$this->relation]
        ];
    }

    public function bind(string $relation)
    {
        if (!in_array(strtolower($relation), ['subscribers', 'subscriptions'])) {
            throw new \Exception('Subscribers или Subscriptions');
        }
        $this->relation = strtolower($relation);
    }

    public function onAdd(): array
    {
        $profile = \Books\Profile\Models\Profile::find(post('id'));
        if ($profile) {
            $profile->{'add' .ucfirst( $this->relation)}($this->profile);
        }
        return $this->render();
    }

    public function onRemove(): array
    {
        $profile = \Books\Profile\Models\Profile::find(post('id'));
        if ($profile) {
            $this->profile->{'remove' . ucfirst( $this->relation)}($profile);
        }
        return $this->render();
    }

    public function render()
    {
        return ['#sub-spawn' => $this->renderPartial('@default',$this->getVals())];
    }
}
