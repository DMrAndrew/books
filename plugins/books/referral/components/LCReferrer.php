<?php namespace Books\Referral\Components;

use App;
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
            $targetLink = post('target_link');
            $this->validateTargetLink($targetLink);

            /**
             * Если на целевую страницу уже существует реферальная ссылка - вернуть ее
             */
            $referrer = $this->user->referrer()->where('target_link', $targetLink)->first();
            if ($referrer) {
                $referrer->touch();
            }

            /**
             * Иначе - создать новую реферальную ссылку
             */
            else {
                $this->user->referrer()->create([
                    'target_link' => $targetLink,
                ]);
            }

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

    /**
     * @param mixed $targetLink
     *
     * @return void
     * @throws Exception
     */
    private function validateTargetLink(mixed $targetLink): void
    {
        $targetLink = trim($targetLink);

        /**
         * Ссылка обязательная
         */
        if ($targetLink == null || mb_strlen($targetLink) == 0) {
            throw new Exception('Необходимо указать ссылку на целевую страницу');
        }

        /**
         * Ссылка должна быть валидным URL адресом
         */
        if(!filter_var($targetLink, FILTER_VALIDATE_URL))
        {
            throw new Exception('Введенная ссылка не является корректным URL-адресом');
        }

        $urlParts = parse_url($targetLink);

        /**
         * Ссылка должна иметь https протокол
         */
        if (App::environment() === 'production') {
            if ($urlParts['scheme'] != 'https') {
                throw new Exception('Ссылка должна использовать защищенный HTTPS-протокол');
            }
        }

        /**
         * Ссылки могут вести только на сам сервиc (домен должен принадлежать сайту)
         */
        $allowedDomains = [
            config('app.url'),
            config('app.com_url'),
        ];

        //if (App::environment() !== 'local') {
            if (!in_array($urlParts['host'], $allowedDomains)) {
                throw new Exception("Ссылка может вести только на внутренние страницы сайта Время книг");
            }
        //}
    }
}
