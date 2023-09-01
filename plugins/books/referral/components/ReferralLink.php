<?php namespace Books\Referral\Components;

use Books\Referral\Contracts\ReferralServiceContract;
use Books\Referral\Models\Referrer;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;

/**
 * ReferralLink Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ReferralLink extends ComponentBase
{
    protected ?User $user;
    protected ?Referrer $referrer;

    public function componentDetails()
    {
        return [
            'name' => 'ReferralLink Component',
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

    public function onRun()
    {
        $this->processReferralLink();

        if ($this->referrer) {
            return Redirect::to($this->referrer->target_link);
        }

        return Redirect::to('/');
    }

    private function processReferralLink()
    {
        $partnerCode = $this->param('code');
        $this->referrer = Referrer::where('code', $partnerCode)->first();

        if ($this->referrer) {
            $referralService = app(ReferralServiceContract::class);

            /**
             * If guest - save cookie
             */
            if ( !Auth::getUser() ) {
                $referralService->saveReferralCookie($partnerCode);
            }
            /**
             * authenticated - save referral
             */
            else {

                $this->user = Auth::getUser();

                /**
                 * Referrer is current user - skip
                 */
                if ($this->referrer->user_id == $this->user->id) {
                    return;
                }

                /**
                 * Create referral
                 */
                $referralService->addReferral($this->referrer, $this->user);
            }

            /**
             * Save referral visit
             */
            $this->referrer->visits()->create([]);
        }
    }
}
