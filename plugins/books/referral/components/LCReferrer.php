<?php namespace Books\Referral\Components;

use Books\Referral\Models\Referrer;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;

/**
 * LCReferrer Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class LCReferrer extends ComponentBase
{
    protected ?User $user;

    public function componentDetails()
    {
        return [
            'name' => 'LCReferrer Component',
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
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser();
    }

    public function onRender()
    {
        $this->page['user'] = $this->user;
        $this->page['referrer'] = $this->getReferrer();
        $this->page['referrer_link'] = $this->getReferrerLink();
    }

    /**
     * @return Referrer|null
     */
    private function getReferrer(): ?Referrer
    {
        return $this->user->referrer;
    }

    /**
     * @return array|RedirectResponse
     */
    public function onGenerateReferralLink(): array|RedirectResponse
    {
        try {
            $this->user->referrer()->create();
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return [];
        }

        return Redirect::refresh();
    }

    /**
     * @return string
     */
    private function getReferrerLink(): string
    {
        $referrer = $this->getReferrer();

        if (!$referrer) {
            return '';
        }

        return url('/referral', ['code' => $referrer->code]);
    }
}
