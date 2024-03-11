<?php

namespace Books\Chat\Components;

use Books\Chat\Classes\MessengerService;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Messenger Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Messenger extends ComponentBase
{
    protected ?User $user;
    protected MessengerService $service;

    public function componentDetails()
    {
        return [
            'name' => 'Messenger Component',
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
        parent::init(); // TODO: Change the autogenerated stub
        $this->user = Auth::getUser();
        $this->service = new MessengerService($this->user->profile);
    }

    public function onRun()
    {
        parent::onRun(); // TODO: Change the autogenerated stub
        if (!$this->user) {
            return;
        }
        $filename = app()->isProduction() ? 'echo' : 'echo-dev';
        $this->addJs("/themes/demo/assets/js/{$filename}.min.js?v=2");
        $this->val();
    }

    public function val(): void
    {
        foreach ($this->service->headerArray() as $key => $value) {
            $this->page[$key] = $value;
        }
    }

    public function onUpdateMessenger(): array
    {
        $data = $this->service->headerArray();
        return [
            '.thread_spawn' => $this->renderPartial('@threads',$data),
            '.unread_thread_count_spawn' => $this->renderPartial('@unread_thread_count',$data)
        ];
    }
}
