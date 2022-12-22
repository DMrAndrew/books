<?php namespace Books\Book\Components;

use Books\Book\Classes\FB2Manager;
use Books\Book\Models\AgeRestrictionsEnum;
use Books\Book\Models\Cycle;
use Event;
use Flash;
use Illuminate\Support\Facades\Redirect;
use Request;
use Exception;
use Session;
use ValidationException;
use Books\Book\Models\Book;
use RainLab\User\Models\User;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use Books\Catalog\Models\Genre;
use Books\Book\Models\CoAuthor;
use October\Rain\Database\Collection;
use Books\FileUploader\Components\ImageUploader;

/**
 * Booker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Booker extends ComponentBase
{
    protected Book $book;
    protected User $user;
    protected $book_id;

    public function init()
    {
        $this->book_id = (int)$this->param('book_id');
        $this->user = Auth::getUser();
        $this->book = $this->book_id ? $this->user?->books()->find($this->book_id) : new Book();
        $component = $this->addComponent(
            ImageUploader::class,
            'coverUploader',
            [
                'deferredBinding' => true,
                'imageWidth' => 168,
                'imageHeight' => 243,
            ]
        );
        $component->bindModel('cover', $this->book);
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
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
        $this->page['tags'] = $this->getTags();
    }

    public function onCreateTag()
    {
        $data = post();
        $string = $data['create_tag_name'] ?? null;
        $tag = $this->user?->tags()->create(['name' => $string]);
        $this->book?->tags()->add($tag, $this->getSessionKey());
        return $this->generateTagInput();
    }

    public function onAddTag()
    {
        $data = post();
        $id = $data['create_tag_id'] ?? null;
        if ($tag = $this->user?->tags()->find($id)) {
            $this->book?->tags()->add($tag, $this->getSessionKey());
            return $this->generateTagInput();
        }
        throw new ValidationException(['tag' => 'Тэг не найден']);

    }

    public function onRemoveTag()
    {
        $data = post();
        $id = $data['delete_tag_id'] ?? null;
        if ($tag = $this->user?->tags()->find($id)) {
            $this->book?->tags()->remove($tag, $this->getSessionKey());
            return $this->generateTagInput();
        }
    }

    public function onSearchTag(): array
    {
        $data = post();
        $string = $data['tag_string'] ?? null;
        if (!$string || strlen($string) < 2) {
            return $this->generateTagInput(['tagString' => $string]);
        }
        $like = $this->user?->tags()->nameLike($string)->get();
        $array = $like->diff($this->getTags());
        $already_has = !!$this->getTags()->first(fn($i) => $i->name === $string);
        $can_create = !$already_has && !!!$like->first(fn($i) => $i->name === $string);
        return $this->generateTagInput([
            'search_result' => $array->toArray(),
            'already_has' => $already_has,
            'can_create' => $can_create,
            'tagString' => $string,
        ]);
    }

    function generateTagInput(array $options = []): array
    {
        return [
            '#tag_input_partial' => $this->renderPartial('@tag_input', ['tags' => $this->getTags(), ...$options])
        ];
    }

    function getTags()
    {
        return $this->book?->tags()->withDeferred($this->getSessionKey())->get();
    }

    public function onSearchCoauthor()
    {
        $name = post('searchCoauthor');
        if ($name && strlen($name) > 2) {
            $array = User::coauthorsAutocomplite($name)?->get()
                ?->diff($this->book->coauthors()->withDeferred($this->getSessionKey())->get())
                ?->diff(Collection::make([Auth::getUser()]))
                ->toArray()
                ?? [];
        } else {
            $array = [];
        }

        return $this->generateCoAuthorInput(['search_array' => $array, 'search_string' => $name]);

    }

    public function onAddCoauthor()
    {
        try {
            if ($user = User::find(post('id'))) {

                $count = $this->book->coauthors()->withDeferred($this->getSessionKey())->count();
                if ($count === 4) {
                    throw new ValidationException(['coauthors' => 'Вы можете добавить до 3 соавторов.']);
                }
                if (!$count) {
                    $this->book->coauthors()->add(Auth::getUser(), $this->getSessionKey(), ['percent' => 100]);
                }
                $this->book->coauthors()->add($user, $this->getSessionKey(), ['percent' => 0]);
            }

            return $this->generateCoAuthorInput();

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    function postCoauthors(): \October\Rain\Support\Collection|\Illuminate\Support\Collection
    {
        return collect(post('coauthors'))->map(function ($item) {
            return new CoAuthor($item);
        });
    }

    public function onSaveBook()
    {
        try {
            $data = post();
            $data['comment_allowed'] = !!$data['comment_allowed'];
            $data['download_allowed'] = !!$data['download_allowed'];
            $data['user_id'] = $this->user->id;

            $book = new Book(collect($data)->only([
                'title', 'annotation', 'comment_allowed', 'download_allowed', 'user_id'
            ])->toArray());
            if ($coauthors = $data['coauthors'] ?? false) {
                if (collect($coauthors)->pluck('percent_value')->sum() !== 100) {
                    throw  new ValidationException(['coauthors' => 'Сумма распределения процентов от продаж должна быть равна 100']);
                }
            }
            if ($cycle = $this->user?->cycles()->find($data['cycle_id'] ?? null)) {
                $book->cycle()->add($cycle, $this->getSessionKey());
            }
            $book->age_restriction = $data['age_restriction'] ?? 0;

            $book->save(null, $this->getSessionKey());
            //Event::fire('books.book.created', [$book]);

            return Redirect::to("/about-book/$book->id");
        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

    }

    /**
     * @throws ValidationException
     */
    public function onModifyPercentValue()
    {
        try {
            $data = post();
            foreach ($data['coauthors'] ?? [] as $datum) {
                $user_id = (int)$datum['user_id'];
                $value = (int)$datum['percent_value'] ?? 0;

                if ($author = $this->book->getDeffered($this->getSessionKey())
                    ->where('master_field', '=', 'coauthors')
                    ->first(fn($bind) => $bind->slave_id === $user_id)) {
                    $author->pivot_data = ['percent' => $value];
                    $author->save();
                }

            }

            return $this->book->getDeffered($this->getSessionKey())
                ->where('master_field', '=', 'coauthors');

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    public function onDeleteCoAuthor()
    {
        try {
            $data = post();
            $user_id = $data['delete_user_id'];
            $this->book->coauthors()->remove(User::find($user_id), $this->getSessionKey());
            if ($this->book->coauthors()->withDeferred($this->getSessionKey())->count() === 1) {
                $this->book->coauthors()->remove(Auth::getUser(), $this->getSessionKey());
            }

            return $this->generateCoAuthorInput();

        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    function getCoAuthors()
    {
        if ($this->getSessionKey()) {
            $coauthors = $this->book->coauthors()->withDeferred($this->getSessionKey())->get();
            $this->book->getDeffered($this->getSessionKey())
                ->where('master_field', '=', 'coauthors')
                ->each(function ($bind) use ($coauthors) {
                    if ($author = $coauthors->first(fn($i) => $i->id === $bind->slave_id)) {
                        $author->pivot = new CoAuthor(['user_id' => $author->id] + ($bind->pivot_data ?? []));
                    }
                    if ($bind->slave_id === Auth::getUser()->id) {
                        $author->pivot->owner = true;
                    }
                });
            return $coauthors;
        }
        return $this->book->coauthors;

    }

    public function generateCoAuthorInput(array $options = []): array
    {
        return ['#coauthorInput' => $this->renderPartial('@coauthorInput', ['coauthors' => $this->getCoAuthors(), ...$options])];
    }

    /**
     * getSessionKey
     */
    public function getSessionKey()
    {
        return post('_session_key', null);
    }

    public function onCreateCycle(): array
    {
        try {
            if ($this->user->cycles()->name(post('name'))->exists()) {
                throw new ValidationException(['name' => 'Цикл уже существует.']);
            }
            $this->user->cycles()->add(new Cycle(post()));
        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
        return [
            '#cycle_input' => $this->renderPartial('@cycle_input', ['cycles' => $this->getCycles()]),
            '#create_cycle_form_partial' => $this->renderPartial('@cycle_create_modal')
        ];
    }

    public function onSearchGenre()
    {

        $name = post('searchgenre');
        if ($name && strlen($name) > 2) {
            $array = Genre::active()
                ->child()
                ->name($name)
                ->get()
                ->diff($this->book->genres()->withDeferred($this->getSessionKey())->get())
                ->toArray();
        } else {
            $array = [];
        }
        return [
            '#genresInput' => $this->renderPartial('@genresInput',
                ['genres' =>
                    $this->book->genres()
                        ->withDeferred($this->getSessionKey())
                        ->get(),
                    'search' => $array,
                    'searchgenrestring' => $name
                ])

        ];
    }

    function getCycles()
    {
        return $this->user?->cycles->toArray() ?? [];
    }


    public function onDeleteGenre(): array
    {
        if ($genre = Genre::find(post('id'))) {
            $this->book->genres()->remove($genre, $this->getSessionKey());
        }

        return $this->generateGenresInput();
    }

    public function onAddGenre(): array
    {

        if ($genre = Genre::find(post('id'))) {
            $this->book->genres()->add($genre, $this->getSessionKey());
        }

        return $this->generateGenresInput();

    }

    protected function generateGenresInput(): array
    {
        return [
            '#genresInput' => $this->renderPartial('@genresInput',
                ['genres' =>
                    $this->book->genres()
                        ->withDeferred($this->getSessionKey())
                        ->get()])
        ];
    }

}
