<?php
namespace Books\Blacklists\Components;

use App\classes\PartialSpawns;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Log;
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
        return [
            'recordsPerPage' => [
                'title' => 'Количество профилей на странице',
                'comment' => 'Количество профилей отображаемых на одной странице',
                'default' => 16,
            ],
        ];
    }

    public function init()
    {
        $authUser = Auth::getUser();
        $this->profile = Profile::query()->find($this->profile_id ?? $authUser?->profile->id);
    }

    public function onRun()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    public function vals()
    {
        return [
            'comments_blacklisted_profiles' => $this->profile?->profiles_blacklisted_in_comments()
                ->paginate((int) $this->property('recordsPerPage', 16)),
            'chat_blacklisted_profiles' => $this->profile?->profiles_blacklisted_in_chat()
                ->paginate((int) $this->property('recordsPerPage', 16)),
        ];
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
            $this->profile->blackListCommentsFor($banProfile);
        } catch (Exception $e) {
            Log::error($e->getMessage());
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
            $this->profile->unBlackListCommentsFor($unBanProfile);
            Flash::success('Профиль пользователя успешно удален из Черного списка');

            return starts_with($this->page->url, '/lc-blacklist') ? $this->renderList() : [];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Flash::error($e->getMessage());
            return [];
        }
    }

    public function renderList(): array
    {
        return [PartialSpawns::SPAWN_LC_BLACKLIST->value => $this->renderPartial('@default', $this->vals())];
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
        if (!Auth::getUser()) {
            return [];
        }

        try {
            $unBanProfile = Profile::findOrFail(post('profile_id'));
            $this->profile->unBlackListChatFor($unBanProfile);
            Flash::success('Профиль пользователя успешно удален из Черного списка');

            return starts_with($this->page->url, '/lc-blacklist') ? $this->renderList() : [];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Flash::error($e->getMessage());
            return [];
        }
    }
}
