<?php namespace Books\Book\Components;

use Books\Book\Classes\PromocodeGenerationLimiter;
use Books\Book\Models\Edition;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Database\Eloquent\Collection;
use Log;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Promocode Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Promocode extends ComponentBase
{
    protected User $user;

    protected ?Edition $edition;

    public function componentDetails()
    {
        return [
            'name' => 'Promocode Component',
            'description' => 'Компонент промокодов'
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

        $this->edition = $this->getEdition();
    }

    public function onRender()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
    }

    public function vals()
    {
        return [
            'editions' => $this->getNotFreeAccountEditions(),
            'editionItem' => $this->getEdition(),
            'promocodes' => $this->getBooksPromocodes()
        ];
    }

    public function getEdition()
    {
        $editions = $this->getNotFreeAccountEditions();

        return $editions
            ->where('id', post('value') ?? post('edition_id') ?? $this->param('edition_id'))
            ->first();
    }

    public function onGetBookPromocodes()
    {
        return [
            '#promocodesList' => $this->renderPartial('promocode/list', ['promocodes' => $this->getBooksPromocodes()])
        ];
    }

    public function onGenerate()
    {
        try {

            /**
             * current user is book author
             */
            if (!$this->edition) {
                Flash::error("Издание не найдено");

                return [];
            }

            /**
             * check promocode limits
             */
            $promoLimiter = new PromocodeGenerationLimiter(profile: $this->user->profile, edition: $this->edition);
            if (!$promoLimiter->canGenerate()) {
                Flash::error($promoLimiter->getReason());

                return [];
            }

            /**
             * generate promocode
             */
            $this->edition->promocodes()->create([
                'profile_id' => $this->user->profile->id,
                'expire_in' => $promoLimiter->getExpireIn(),
            ]);

            Flash::success('Новый промокод сгенерирован');

            return [
                '#promocodesList' => $this->renderPartial('promocode/list', ['promocodes' => $this->getBooksPromocodes()])
            ];

        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            Flash::error($ex->getMessage());
            return [];
        }
    }

    /**
     * @return Collection|null
     */
    private function getBooksPromocodes(): ?Collection
    {
        return $this->edition?->promocodes()
            ->with(['user'])
            ->get() ?? new Collection();
    }

    public function query(){
        return $this->user->toBookUser()->booksInAuthorOrder()->notFree();
    }

    /**
     * @return Collection
     */
    private function getNotFreeAccountEditions(): Collection
    {
        $books = $this->user->toBookUser()->booksInAuthorOrder()->get();

        return Edition::query()
            ->whereIn('book_id', $books->pluck('id')->toArray())
            ->free(false)
            ->get();
    }
}
