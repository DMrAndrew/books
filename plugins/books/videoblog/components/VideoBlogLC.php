<?php namespace Books\Videoblog\Components;

use Books\Book\Classes\Services\TextCleanerService;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Profile\Models\Profile;
use Books\Videoblog\Classes\Enums\VideoBlogPostStatus;
use Books\Videoblog\Classes\Services\VideoBlogPostService;
use Books\Videoblog\Models\Videoblog;
use Carbon\CarbonPeriod;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use October\Rain\Support\Facades\Event;
use RainLab\User\Facades\Auth;
use Redirect;
use ValidationException;
use Validator;

/**
 * BlogLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class VideoBlogLC extends ComponentBase
{
    protected Profile $profile;
    protected int $postsCount;
    protected ?Videoblog $post;

    public function componentDetails()
    {
        return [
            'name' => 'VideoBlogLC Component',
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

        $this->postsCount = $this->profile->videoblog_posts()->count();

        if ($this->param('post_id') !== null) {
            $postId = $this->param('post_id');
            $this->post = $this->profile->videoblog_posts()->findOrFail($postId);
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
            $data['status'] = VideoBlogPostStatus::PUBLISHED;
            $data['published_at'] = now();

            /**
             * Clean html content
             */
            $data['content'] = TextCleanerService::cleanContent($data['content']);

            /**
             * Validate
             */
            $validator = Validator::make(
                $data->toArray(),
                collect((new Videoblog())->rules)->only([
                    'title', 'content', 'published_at'
                ])->toArray(),
                collect((new Videoblog())->customMessages)->only([
                    'title', 'content', 'published_at'
                ])->toArray(),
                collect((new Videoblog())->attributeNames)->only([
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
                $post = $this->profile->videoblog_posts()->findOrFail($data['post_id']);

                $post->update($data->toArray());
            } else {
                $post = $this->profile->videoblog_posts()->create($data->toArray());
            }

            VideoBlogPostService::getYoutubeEmbedCode($post);

            if ($post->wasRecentlyCreated) {
                Event::fire('books.videoblog::post.published', [$this->profile, $post]);
            }

            /**
             * Open Post
             */
            return Redirect::to('/videoblog/' . $post->slug)->setLastModified(now());

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
                $post = $this->profile->videoblog_posts()->findOrFail($data['post_id']);
                $post->delete();
            }

            return Redirect::to('/lc-videoblog/');

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

        $manager->register('lc-videoblog-editor', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Видеоблог', url('/lc-videoblog'));

            if ($this->post) {
                $trail->push($this->post->title);
            } else {
                $trail->push('Новая запись в видеоблоге');
            }
        });
    }
}
