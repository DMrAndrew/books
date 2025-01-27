<?php namespace Books\Blog\Components;

use Books\Blog\Classes\Enums\PostStatus;
use Books\Blog\Classes\Services\PostService;
use Books\Blog\Models\Post;
use Books\Book\Classes\Services\TextCleanerService;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Notifications\Classes\Events\PostPublished;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\NotificationSender;
use Illuminate\Support\Arr;
use October\Rain\Support\Facades\Event;
use RainLab\Notify\Classes\Notifier;
use RainLab\User\Facades\Auth;
use Redirect;
use ValidationException;
use Validator;

/**
 * BlogLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class BlogLC extends ComponentBase
{
    protected Profile $profile;
    protected int $postsCount;
    protected ?Post $post;

    public function componentDetails()
    {
        return [
            'name' => 'BlogLC Component',
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
        $user = Auth::getUser();
        $this->profile = $user->profile;

        $this->postsCount = $this->profile->posts()->count();

        if ($this->param('post_id') !== null) {
            $postId = $this->param('post_id');
            $this->post = $this->profile->posts()->findOrFail($postId);
        } else {
            $this->post = null;
        }

        $this->prepareVals();

        $this->registerBreadcrumbs();
    }

    public function prepareVals()
    {
        $this->page['postsCount'] = $this->postsCount;
        $this->page['post'] = $this->post;
        $this->page['profile'] = $this->profile;
        $this->page['times'] = collect(CarbonPeriod::create(today(), '1 hour', today()->copy()->addHours(23))->toArray())->map->format('H:i');
    }

    /**
     * @return RedirectResponse|array
     */
    public function onSavePost(): RedirectResponse|array
    {
        try {
            $data = collect(post());
            $data['user_id'] = $this->profile->user->id;
            $data['status'] = PostStatus::PUBLISHED;
            $data['published_at'] = now();

            // скрыть отложенную публикацию до востребования
//            if ($status = $data['action'] ?? false) {
//                switch ($status) {
//                    case 'published_at':
//
//                        $data['status'] = PostStatus::PLANNED;
//                        if (!isset($data['published_at_date'])) {
//                            new ValidationException(['published_at' => 'Укажите дату публикации.']);
//                        }
//                        if (!isset($data['published_at_time'])) {
//                            new ValidationException(['published_at' => 'Укажите время публикации.']);
//                        }
//                        if (!Carbon::canBeCreatedFromFormat($data['published_at_date'] ?? '', 'd.m.Y')) {
//                            throw new ValidationException(['published_at' => 'Не удалось получить дату публикации. Укажите дату в формате d.m.Y']);
//                        }
//                        $data['published_at'] = Carbon::createFromFormat('d.m.Y', $data['published_at_date'])->setTimeFromTimeString($data['published_at_time']);
//                        if (Carbon::now()->gte($data->get('published_at'))) {
//                            throw new ValidationException(['published_at' => 'Дата и время публикации должны быть больше текущего времени.']);
//                        }
//                        break;
//
//                    case 'save_as_draft':
//
//                        $data['status'] = PostStatus::DRAFT;
//                        break;
//
//                    case 'publish_now':
//
//                        $data['status'] = PostStatus::PUBLISHED;
//                        break;
//                }
//            }

            /**
             * Clean html content
             */
            $data['content'] = TextCleanerService::cleanContent($data['content']);

            /**
             * Validate
             */
            $validator = Validator::make(
                $data->toArray(),
                collect((new Post())->rules)->only([
                    'title', 'content', 'published_at'
                ])->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            /**
             * Save
             */
            if (isset($data['post_id'])) {
                $post = $this->profile->posts()->findOrFail($data['post_id']);

                $post->update($data->toArray());
            } else {
                $post = $this->profile->posts()->create($data->toArray());
            }

            PostService::replaceBase64ImagesWithFiles($post);

            if ($post->wasRecentlyCreated) {
                Event::dispatch('books.blog::post.published', [$this->profile, $post]);
            }

            /**
             * Open Post
             */
            return Redirect::to('/blog/' . $post->slug)->setLastModified(now());

        } catch (Exception $e) {
            Flash::error($e->getMessage());

            return [];
        }
    }

    /**
     * @return RedirectResponse|array
     */
    public function onDeletePost(): RedirectResponse|array
    {
        try {
            $data = collect(post());

            if (isset($data['post_id'])) {
                $post = $this->profile->posts()->findOrFail($data['post_id']);
                $post->delete();
            }

            return Redirect::to('/lc-blog/');

        } catch (Exception $e) {
            Flash::error($e->getMessage());

            return [];
        }
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-blog-editor', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Блог', url('/lc-blog'));

            if ($this->post) {
                $trail->push($this->post->title);
            } else {
                $trail->push('Новая запись в блоге');
            }
        });
    }
}
