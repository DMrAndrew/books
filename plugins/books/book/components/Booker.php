<?php namespace Books\Book\Components;

use ApplicationException;

use Flash;
use Request;
use Exception;
use ValidationException;
use Books\Book\Models\Book;
use Books\Book\Models\Cycle;
use RainLab\User\Models\User;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use Books\Catalog\Models\Genre;
use October\Rain\Database\Collection;
use Illuminate\Support\Facades\Redirect;
use Books\Book\Models\AgeRestrictionsEnum;
use Books\FileUploader\Components\ImageUploader;
use Books\Book\Classes\Services\CreateBookService;
use Books\Book\Classes\Services\UpdateBookService;
use Books\Book\Classes\Services\BookServiceInterface;
/**
 * Booker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Booker extends ComponentBase
{
    protected Book $book;
    protected User $user;
    protected int $book_id;
    protected BookServiceInterface $service;

    public function init()
    {
        $this->book_id = (int)$this->param('book_id');
        $this->user = Auth::getUser();
        if (!$this->user) {
            throw new ApplicationException('User required');
        }
        $this->book = $this->user?->books()->find($this->book_id) ?? new Book();
        $this->service = new ($this->book_id ? UpdateBookService::class : CreateBookService::class)(session_key: $this->getSessionKey(), book: $this->book, user: $this->user);
        $component = $this->addComponent(
            ImageUploader::class,
            'coverUploader',
            [
                'modelClass' => Book::class,
                'deferredBinding' => true,
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
            'deferredBinding' => [
                'title' => 'Deferred Binding',
                'description' => 'Используется при создании новой книги',
                'type' => 'checkbox',
                'default' => true,
            ],
        ];
    }


    public function onRun()
    {
        $this->page['book'] = $this->book;
        $this->page['age_restrictions'] = AgeRestrictionsEnum::cases();
        $this->page['cycles'] = $this->getCycles();
        $this->page['tags'] = $this->service->getTags();
    }


    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    public function onCreateTag(): array
    {
        $string = post('create_tag_name');
        $tag = $this->user?->tags()->create(['name' => $string]);
        $this->service->addTag($tag);
        return $this->generateTagInput();
    }

    /**
     * @throws ValidationException
     */
    public function onAddTag(): array
    {
        if ($tag = $this->user->tags()->find(post('create_tag_id'))) {
            $this->service->addTag($tag);
            return $this->generateTagInput();
        }
        throw new ValidationException(['tag' => 'Тэг не найден']);

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

    public function onSearchCoauthor()
    {
        try {
            $name = post('searchCoauthor');
            if ($name && strlen($name) > 2) {
                $array = User::coauthorsAutocomplite($name)?->get()
                    ?->diff($this->service->getCoAuthors())
                    ?->diff(Collection::make([$this->user]))
                    ->toArray()
                    ?? [];
            } else {
                $array = [];
            }

            return $this->generateCoAuthorInput(['search_array' => $array, 'search_string' => $name]);

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

    }

    public function onAddCoauthor()
    {
        try {
            $count = $this->service->getCoAuthors()->count();
            if ($count > 3) {
                throw new ValidationException(['coauthors' => 'Вы можете добавить до 3 соавторов.']);
            }
            if ($user = User::find(post('id'))) {
                $this->service->addCoAuthor($user);
            }

            return $this->generateCoAuthorInput();

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }


    public function onSaveBook()
    {
        try {
            $data = post();
            if ($coauthors = $data['coauthors'] ?? false) {
                if (collect($coauthors)->pluck('percent_value')->sum() !== 100) {
                    throw  new ValidationException(['coauthors' => 'Сумма распределения процентов от продаж должна быть равна 100.']);
                }
            }

            $fillable = collect($data)->only([
                'title', 'annotation', 'comment_allowed', 'download_allowed', 'cycle_id', 'age_restriction'
            ])->toArray();

            $book = $this->service->save($fillable);

            return $this->book_id ?
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
            foreach (post('coauthors') ?? [] as $datum) {
                $this->service->modifyCoAuthorPercent((int)$datum['user_id'] ?? null, (int)$datum['percent_value'] ?? 0);
            }

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    public function onDeleteCoAuthor()
    {
        try {
            $this->service->removeCoAuthor(User::find(post('delete_user_id')));

            return $this->generateCoAuthorInput();

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
                ->child()
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


    public function generateCoAuthorInput(array $options = []): array
    {
        return ['#coauthorInput' => $this->renderPartial('@coauthorInput', ['coauthors' => $this->service->getCoAuthors(pivot: true), ...$options])];
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
        return post('_session_key', null);
    }

}
