<?php namespace Books\Blacklists\Components;

use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Blacklist Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Blacklist extends ComponentBase
{
    protected ?User $authUser;
    protected ?Profile $profile;

    public function componentDetails()
    {
        return [
            'name' => 'Blacklist Component',
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
        $authUser = Auth::getUser();
        $this->profile = Profile::query()->find($this->profile_id ?? $authUser?->profile->id);
    }

    /**
     * @return array
     */
    public function onAddToCommentsBlacklist(): array
    {
        if (!Auth::getUser()) {
            return [];
        }

        try {
            $banProfile = Profile::findOrFail(post('profile_id'));
            $this->profile->blacklistProfileInComments($banProfile);
        } catch (\Exception $e) {
            Flash::error($e->getMessage());
            return [];
        }

        Flash::success('Профиль пользователя успешно добавлен в Черный список');

        return [];
    }

    /**
     * @return array
     */
    public function onRemoveFromCommentsBlacklist(): array
    {
        if (!Auth::getUser()) {
            return [];
        }

        try {
            $unBanProfile = Profile::findOrFail(post('profile_id'));
            $this->profile->unBlacklistProfileInComments($unBanProfile);
        } catch (\Exception $e) {
            Flash::error($e->getMessage());
            return [];
        }

        Flash::success('Профиль пользователя успешно удален из Черного списка');

        return [];
    }

    /**
     * todo
     *
     * @return array
     */
    public function onAddToChatBlacklist(): array
    {
        return [];
    }

    /**
     * todo
     *
     * @return array
     */
    public function onRemoveFromChatBlacklist(): array
    {
        return [];
    }
}
