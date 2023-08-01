<?php namespace Books\Blog\Components;

use Books\Blog\Models\Post;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
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

        $this->post = Post
                ::where('id', $this->param('post_id'))
                ->where('profile_id', $this->profile->id)
                ->first()
            ?? new Post();

        $this->prepareVals();
    }

    public function prepareVals()
    {
        $this->page['post'] = $this->post;
        $this->page['profile'] = $this->profile;
    }

    public function onSavePost()
    {
        try {
            $data = collect(post());

            $validator = Validator::make(
                $data->toArray(),
                collect((new Post())->rules)->only([
                    'title', 'content',
                ])->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            //dd($this->post);
            if (isset($data['post_id'])) {
                $post = Post
                    ::where('id', $data['post_id'])
                    ->where('profile_id', $this->profile->id)
                    ->firstOrFail();

                $post->update([
                    'title' => $data['title'],
                    'content' => $data['title'],
                ]);
            } else {
                $post = Post::create([
                    'profile_id' => $this->profile->id,
                    'title' => $data['title'],
                    'content' => $data['content'],
                ]);
            }

            return Redirect::to('/blog/' . $post->slug)->setLastModified(now());

        } catch (Exception $e) {
            Flash::error($e->getMessage());

            return [];
        }
    }
}
