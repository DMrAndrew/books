<?php namespace Books\Blog\Components;

use Books\Blog\Models\Post;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
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
    }

    public function prepareVals()
    {
        $this->page['postsCount'] = $this->postsCount;
        $this->page['post'] = $this->post;
        $this->page['profile'] = $this->profile;
    }

    /**
     * @return RedirectResponse|array
     */
    public function onSavePost(): RedirectResponse|array
    {
        try {
            $data = collect(post());

            /**
             * Validate
             */
            $validator = Validator::make(
                $data->toArray(),
                collect((new Post())->rules)->only([
                    'title', 'content',
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

                $post->update([
                    'title' => $data['title'],
                    'content' => $data['content'],
                ]);
            } else {
                $post = $this->profile->posts()->create([
                    'title' => $data['title'],
                    'content' => $data['content'],
                ]);
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
}
