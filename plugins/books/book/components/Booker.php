<?php namespace Books\Book\Components;

use ApplicationException;
use Books\Book\Classes\BookService;
use Books\Book\Models\AgeRestrictionsEnum;
use Books\Book\Models\Book;
use Books\Book\Models\Cycle;
use Books\Catalog\Models\Genre;
use Books\FileUploader\Components\ImageUploader;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Support\Facades\Redirect;
use October\Rain\Database\Collection;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Request;
use ValidationException;

/**
 * Booker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Booker extends ComponentBase
{
    protected ?Book $book;
    protected User $user;
    protected BookService $service;

    public function init()
    {

        $this->user = User::find($this->property('user_id')) ?? Auth::getUser();
        if (!$this->user) {
            throw new ApplicationException('User required');
        }
        $this->book = $this->user->profile->books()->find($this->property('book_id')) ?? new Book();

        $this->service = new BookService(user: $this->user, book: $this->book, session_key: $this->getSessionKey());

        $component = $this->addComponent(
            ImageUploader::class,
            'coverUploader',
            [
                'modelClass' => Book::class,
                'deferredBinding' => !!!$this->book->id,
                'imageWidth' => 168,
                'imageHeight' => 243,
            ]
        );
        $component->bindModel('cover', $this->book);
    }

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Booker Component',
            'description' => 'Компонент создания и редактирования книги'
        ];
    }

    public function defineProperties()
    {
        return [
            'user_id' => [
                'title' => 'Auth user',
                'description' => 'Пользователь',
                'type' => 'string',
                'default' => null,
            ],
            'book_id' => [
                'title' => 'Book',
                'description' => 'Книга пользователя',
                'type' => 'string',
                'default' => null,
            ]
        ];
    }


    public function onRun()
    {
        $this->page['ebook'] = $this->book->ebook;
        $this->page['age_restrictions'] = AgeRestrictionsEnum::cases();
        $this->page['cycles'] = $this->getCycles();
    }


    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    /**
     */
    public function onAddTag(): array
    {
        $this->service->addTag(post('tag_name'));
        return $this->generateTagInput();
    }

    /**
     * @throws ValidationException
     */
    public function onRemoveTag()
    {
        if ($tag = $this->user->tags()->find(post('delete_tag_id'))) {
            $this->service->removeTag($tag);
            return $this->generateTagInput();
        }
        throw new ValidationException(['tag' => 'Тэг не найден']);
    }

    public function onSearchTag(): array
    {
        $string = post('tag_string');
        if (!$string || strlen($string) < 2) {
            return $this->generateTagInput(['tagString' => $string]);
        }
        $like = $this->user?->tags()->nameLike($string)->get();
        $exists = $this->service->getTags();
        $array = $like->diff($exists);
        $already_has = !!$exists->first(fn($i) => $i->name === $string);
        $can_create = !$already_has && !!!$like->first(fn($i) => $i->name === $string);
        return $this->generateTagInput([
            'search_result' => $array->toArray(),
            'already_has' => $already_has,
            'can_create' => $can_create,
            'tagString' => $string,
        ]);
    }

    public function onSearchAuthor()
    {
        try {
            $name = post('searchAuthor');
            if ($name && strlen($name) > 2) {
                $array = Profile::searchByString($name)?->get()
                    ?->diff($this->service->getProfiles())
                    ?->diff(Collection::make([$this->user->profile]))
                    ->toArray()
                    ?? [];
            } else {
                $array = [];
            }


            return $this->generateAuthorInput(['search_array' => $array, 'search_string' => $name]);

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

    }

    public function onAddAuthor()
    {

        try {
            $count = $this->service->getProfiles()->count();
            if ($count > 3) {
                throw new ValidationException(['authors' => 'Вы можете добавить до 3 соавторов.']);
            }
            if ($profile = Profile::find(post('id'))) {
                $this->service->addProfile($profile);
            }

            return $this->generateAuthorInput();

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }


    public function onSaveBook()
    {
        try {
            $redirect = !!$this->book->id;
            $book = $this->service->save(post());

            return $redirect ?
                ['#about-header' => $this->renderPartial('book/about-header', ['book' => $book])]
                : Redirect::to("/about-book/$book->id");

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    /**
     * @throws ValidationException|Exception
     */
    public function onModifyPercentValue()
    {
        try {
            collect(post('authors'))
                ->map(fn($i) => [
                    'profile' => Profile::find($i['profile_id'] ?? null),
                    'value' => $i['percent_value']])
                ->filter(fn($i) => !!$i['profile'])
                ->each(function ($i) {
                    $this->service->setPercent(...$i);
                });


        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    public function onDeleteAuthor()
    {
        try {
            $this->service->removeProfile(Profile::find(post('delete_profile_id')));
            return $this->generateAuthorInput();

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    public function onCreateCycle(): array
    {
        try {
            if ($this->user->cycles()->name(post('name'))->exists()) {
                throw new ValidationException(['name' => 'Цикл уже существует.']);
            }
            $this->user->cycles()->add(new Cycle(post()));
            $this->book->cycle = $this->user->cycles()->latest()->first();

            return [
                '#cycle_input' => $this->renderPartial('@cycle_input', ['cycles' => $this->getCycles(), 'cycle_id' => $this->book->cycle->id]),
                '#create_cycle_form_partial' => $this->renderPartial('@cycle_create_modal')
            ];

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    public function onSearchGenre()
    {
        $name = post('searchgenre');
        if ($name && strlen($name) > 2) {
            $array = Genre::active()
                ->name($name)
                ->get()
                ->diff($this->service->getGenres())
                ->toArray();
        } else {
            $array = [];
        }

        return $this->generateGenresInput([
            'search' => $array,
            'searchgenrestring' => $name
        ]);
    }

    function getCycles()
    {
        return $this->user?->cycles->toArray() ?? [];
    }

    public function onDeleteGenre(): array
    {
        if ($genre = Genre::find(post('id'))) {
            $this->service->removeGenre($genre);
        }

        return $this->generateGenresInput();
    }

    public function onAddGenre(): array
    {
        if ($genre = Genre::find(post('id'))) {
            $this->service->addGenre($genre);
        }

        return $this->generateGenresInput();
    }

    function generateTagInput(array $options = []): array
    {
        return ['#tag_input_partial' => $this->renderPartial('@tag_input', ['tags' => $this->service->getTags(), ...$options])];
    }


    public function generateAuthorInput(array $options = []): array
    {
        return ['#authorInput' => $this->renderPartial('@authorInput', ['authors' => $this->service->getProfiles(pivot: true), 'user' => $this->user, ...$options])];
    }

    protected function generateGenresInput(array $options = []): array
    {
        return ['#genresInput' => $this->renderPartial('@genresInput', ['genres' => $this->service->getGenres(), ...$options])];
    }

    /**
     * getSessionKey
     */
    public function getSessionKey()
    {
        return post('_session_key');
    }

}
