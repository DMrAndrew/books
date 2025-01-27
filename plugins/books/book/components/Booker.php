<?php

namespace Books\Book\Components;

use ApplicationException;
use Books\Book\Classes\BookService;
use Books\Book\Classes\BookUtilities;
use Books\Book\Classes\Enums\AgeRestrictionsEnum;
use Books\Book\Classes\Services\TextCleanerService;
use Books\Book\Models\Book;
use Books\Book\Models\Tag;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Catalog\Models\Genre;
use Books\FileUploader\Components\ImageUploader;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Support\Facades\Redirect;
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

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Booker Component',
            'description' => 'Компонент создания и редактирования книги',
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
            ],
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser() ?? throw new ApplicationException('User required');
        $this->book = $this->user->profile->books()->find((int) $this->property('book_id')) ?? new Book();

        $this->service = new BookService(user: $this->user, book: $this->book, session_key: $this->getSessionKey());

        $component = $this->addComponent(
            ImageUploader::class,
            'coverUploader',
            [
                'modelClass' => Book::class,
                'deferredBinding' => ! (bool) $this->book->id,
                'imageWidth' => 168,
                'imageHeight' => 243,
            ]
        );
        $component->bindModel('cover', $this->book);
        $this->page['ebook'] = $this->book->ebook;
        $this->page['age_restrictions'] = AgeRestrictionsEnum::cases();
        $this->page['cycles'] = $this->getCycles();
        $this->page['genres_list'] = Genre::public()->get()->diff($this->book->genres);

        $this->registerBreadcrumbs();
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    public function onSaveBook()
    {
        try {
            $data = post();
            $data['annotation'] = TextCleanerService::cleanContent($data['annotation']);

            if ($data['annotation'] && BookUtilities::countContentLengthForContent($data['annotation']) > 2000) {
                throw new ValidationException(['annotation' => 'Аннотация не должна превышать 2000 символов.']);
            }
            if ($this->service->getGenres()->count() === 0) {
                throw new ValidationException(['genres' => 'Укажите хотя бы один жанр.']);
            }

            $book = $this->service->from($data);
            $redirect = (bool) $this->book->id;

            return ! $redirect ?
                [
                    '#about-header' => $this->renderPartial('book/about-header', ['book' => $book]),
                ]
                : Redirect::to("/about-book/$book->id");
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return [];
        }
    }

    /**
     * @throws ValidationException|Exception
     */
    public function onModifyPercentValue()
    {
        try {
            collect(post('authors'))
                ->whereNotNull('profile_id')
                ->whereBetween('percent_value', [0, 100])
                ->map(fn ($i) => [
                    'profile' => Profile::find($i['profile_id']),
                    'value' => $i['percent_value'],
                ])
                ->each(function ($i) {
                    $this->service->setPercent(...$i);
                });
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onCreateCycle()
    {
        try {

            $name = mb_ucfirst(trim(post('name')));
            if ($this->user->profile->cyclesWithShared()->name($name)->exists()) {
                throw new ValidationException(['name' => 'Цикл уже существует.']);
            }
            $cycle = $this->user->profile->cycles()->create(array_merge(['user_id' => $this->user->id, 'name' => $name]));

            return [
                '#cycle_input' => $this->renderPartial('@cycle_input', ['cycles' => $this->getCycles(), 'cycle_id' => $cycle->id]),
                '#create_cycle_form_partial' => $this->renderPartial('@cycle_create_modal'),
            ];
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return [];
        }
    }

    public function getCycles()
    {
        return $this->user?->profile->cyclesWithShared()->with('profile')->get()->toArray() ?? [];
    }

    public function onSearchTag()
    {
        $term = trim(post('term'));
        if (! $term || strlen($term) < 2) {
            return [];
        }

        $exists = $this->service->getTags();
        $like = Tag::query()
            ->nameLike($term)
            ->whereNotIn('id', $exists->pluck('id')->toArray())
            ->get();

        $already_has = (bool) $exists->first(fn ($i) => mb_strtolower($i->name) === mb_strtolower($term));
        $can_create = ! $already_has && ! (bool) $like->first(fn ($i) => mb_strtolower($i->name) === mb_strtolower($term));

        $res = [];

        if ($already_has) {
            $res[] = [
                'disabled' => true,
                'htm' => $this->renderPartial('select/option', ['placeholder' => 'Тэг «'.$term.'» уже добавлен на страницу']),
            ];
        }
        $res = array_merge($res, collect($like)->map(function ($item) {
            return [
                'id' => $item->id,
                'label' => $item->name,
                'htm' => $this->renderPartial('select/option', ['label' => $item->name]),
                'handler' => $this->alias.'::onAddTag',
            ];
        })->toArray());

        if ($can_create) {
            $res[] = [
                'label' => $term,
                'handler' => $this->alias.'::onAddTag',
                'htm' => $this->renderPartial('select/option', [
                    'prepend_icon' => '#plus-stroked-16',
                    'text' => 'Создать новый тэг «'.$term.'»',
                    'active' => true,
                    'prepend_separator' => $array[0] ?? false,
                ]),
            ];
        }

        return $res;
    }

    //    public function onSearchGenre()
    //    {
    //        $term = post('term');
    //        if (!$term && strlen($term) < 3) {
    //            return [];
    //        }
    //
    //        $array = Genre::public()
    //            ->name($term)
    //            ->get()
    //            ->diff($this->service->getGenres());
    //
    //        return collect($array)->map(function ($item) {
    //            return [
    //                'id' => $item->id,
    //                'label' => $item->name,
    //                'htm' => $this->renderPartial('select/option', ['label' => $item->name]),
    //                'handler' => $this->alias . '::onAddGenre',
    //            ];
    //        })->toArray();
    //    }

    public function onSearchAuthor()
    {
        try {
            $name = post('term');
            if (! $name && strlen($name) < 1) {
                return [];
            }

            $array = Profile::searchByString($name)?->get()
                ?->diff($this->service->getProfiles())
                ?->diff($this->user->profiles()->get());

            return $array->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->username,
                    'htm' => $this->renderPartial('select/option', ['label' => $item->username." (id: $item->id)"]),
                    'handler' => $this->alias.'::onAddAuthor',
                ];
            })->toArray();
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onAddAuthor()
    {
        try {
            if ($this->service->getProfiles()->count() > 2) {
                throw new ValidationException(['authors' => 'Вы можете добавить до 2 соавторов.']);
            }
            if ($profile = Profile::find(post('item')['id'] ?? null)) {
                $this->service->addProfile($profile);
            }

            return $this->generateAuthorInput(['autofocus' => true]);
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onAddTag(): array
    {
        if ($this->service->getTags()->count() > 7) {
            throw new ValidationException(['tags' => 'Вы можете добавить до 8 тэгов.']);
        }
        $this->service->addTag(Tag::find(post('item')['id'] ?? null) ?? post('item')['value'] ?? null);

        return $this->generateTagInput(['autofocus' => true]);
    }

    public function onAddGenre()
    {

        try {
            if ($genres = Genre::query()->public()->find(collect(post('items'))->pluck('value')->toArray())) {
                if ($genres->count() > 4) {
                    throw new ValidationException(['genres' => 'Вы можете добавить до 4 жанров.']);
                }
                $this->service->syncGenres($genres);
            }

            return $this->generateGenresInput();
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return $this->generateGenresInput();
        }
    }

    public function onDeleteAuthor()
    {
        try {
            $this->service->removeProfile(Profile::find(post('delete_profile_id')));

            return $this->generateAuthorInput();
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    //    public function onDeleteGenre(): array
    //    {
    //        if ($genre = Genre::find(post('id'))) {
    //            $this->service->removeGenre($genre);
    //        }
    //
    //        return $this->generateGenresInput();
    //    }

    /**
     * @throws ValidationException
     */
    public function onRemoveTag()
    {
        if ($tag = Tag::query()->find(post('delete_tag_id'))) {
            $this->service->removeTag($tag);

            return $this->generateTagInput();
        }
        throw new ValidationException(['tag' => 'Тэг не найден']);
    }

    public function generateTagInput(array $options = []): array
    {
        return ['#input-tags' => $this->renderPartial('@input-tags', ['tags' => $this->service->getTags(), ...$options])];
    }

    public function generateAuthorInput(array $options = []): array
    {
        return ['#input-authors' => $this->renderPartial('@input-authors', ['authors' => $this->service->getProfiles(pivot: true), 'user' => $this->user, ...$options])];
    }

    protected function generateGenresInput(array $options = []): array
    {
        return ['#input-genres' => $this->renderPartial('@input-genres', [
            'genres' => $this->service->getGenres(),
            'genres_list' => Genre::public()->get()->diff($this->service->getGenres()),
            ...$options,
        ])];
    }

    /**
     * getSessionKey
     */
    public function getSessionKey()
    {
        return post('_session_key');
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('about-book', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Книги', '/lc-books');
            $trail->push($this->book->title);
        });

    }
}
