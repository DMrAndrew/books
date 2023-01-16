<?php namespace Books\Book\Components;

use ApplicationException;
use Books\Book\Classes\FB2Manager;
use Books\Book\Models\Book;
use Books\Book\Models\EbookEdition;
use Books\FileUploader\Components\FileUploader;
use Books\FileUploader\Components\ImageUploader;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Input;
use October\Rain\Database\Builder;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use Request;
use ValidationException;
use Validator;
use function Symfony\Component\Translation\t;

/**
 * LCBooker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class LCBooker extends ComponentBase
{
    protected User $user;
    protected ?bool $is_owner = null;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'LCBooker Component',
            'description' => 'Компонент книг личного кабинета'
        ];
    }

    public function init()
    {
        $this->user = Auth::getUser();
    }

    public function onRender()
    {
        $this->page['authorships'] = $this->getAuthorships();
    }

    function getAuthorships(): Collection
    {
        return $this->user->profile->authorshipsAs($this->getOwnerFilter())
            ->orderByDesc('sort_order')
            ->with(['book', 'book.profile', 'book.ebook', 'book.cover'])
            ->get();
    }

    public function onChangeOrder()
    {
        //TODO
        $sequence = collect(post('sequence'));
        $authorships = $this->getAuthorships();
        $newsequence = (collect([$sequence, $authorships->pluck('sort_order')])->map->reverse()->map->values()->map->toArray())->toArray();
        $authorships->first()->setSortableOrder(...$newsequence);

        return [
            '#books_list_partial' => $this->renderPartial('@default', ['authorships' => $this->getAuthorships()])
        ];
    }

    public function onUploadFile()
    {
        try {
            $uploadedFile = (new EbookEdition())->fb2()->withDeferred($this->getSessionKey())->get()?->first();
            if (!$uploadedFile) {
                throw new ValidationException(['fb2' => 'Файл не найден.']);
            }

            $book = (new FB2Manager(user: $this->user, session_key: $this->getSessionKey()))->apply($uploadedFile);
            $book->ebook->save(null, $this->getSessionKey());

            return Redirect::to('/lc-books');

        } catch (Exception $ex) {
            throw new ValidationException(['fb2' => $ex->getMessage()]);
        }

    }

    /**
     * defineProperties for the component
     *
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    public function getSessionKey()
    {
        return post('_session_key');
    }

    public function getOwnerFilter()
    {
        return post('is_owner') ?? $this->is_owner;
    }
}
