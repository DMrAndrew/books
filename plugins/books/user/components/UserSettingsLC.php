<?php

namespace Books\User\Components;

use AjaxException;
use Books\User\Classes\UserService;
use Cms\Classes\ComponentBase;
use Country;
use Exception;
use Flash;
use Mobecan\SocialConnect\Classes\ProviderManager;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Url;
use ValidationException;

/**
 * UserSettingsLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class UserSettingsLC extends ComponentBase
{
    protected User $user;

    protected UserService $service;

    public function componentDetails()
    {
        return [
            'name' => 'UserSettingsLC Component',
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
        if ($r = redirectIfUnauthorized()) {
            return $r;
        }
        $this->user = Auth::getUser();
        $this->service = new UserService($this->user);
        $this->vals();
    }

    public function vals()
    {
        $this->page['user'] = $this->user;
        $this->page['countries'] = Country::query()->isEnabled()->orderBy('is_pinned', 'desc')->orderBy('name', 'asc')->get();
        $this->page['socials'] = $this->getSocials();
    }

    public function getSocials()
    {
        return collect(ProviderManager::instance()->listProvidersEnabled())
            ->map(function ($provider) {
                return [
                    'provider' => $provider,
                    'alias' => $provider['alias'],
                    'url' => URL::route('mobecan_socialconnect_provider', [$provider['alias'], 's=lc-settings']),
                    'label' => $provider['label'],
                    'tag' => $provider['tag'],
                    'short_tag' => match (strtolower($provider['tag'])) {
                        'yandex' => 'ya',
                        'odnoklassniki' => 'ok',
                        default => $provider['tag']
                    },
                    'exists' => $this->user->mobecan_socialconnect_providers()->where('provider_id', $provider['alias'])->exists(),
                ];
            });
    }

    public function onUpdateCommon()
    {
        try {
            $this->service->update(post());
            Flash::success('Настройки успешно сохранены');
        } catch (Exception $ex) {
            if ($ex instanceof ValidationException) {
                throw $ex;
            }
            Flash::error($ex->getMessage());
            $this->vals();
            throw new AjaxException([
                '#common-form' => $this->renderPartial('@common'),
            ]);
        }
    }
}
