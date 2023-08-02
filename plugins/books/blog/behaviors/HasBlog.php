<?php

namespace Books\Blog\Behaviors;

use Books\Blog\Models\Post;
use Books\Profile\Models\Profile;
use October\Rain\Extension\ExtensionBase;

class HasBlog extends ExtensionBase
{
    public function __construct(protected Profile $profile)
    {
        $this->profile->hasMany['posts'] = [Post::class, 'key' => 'profile_id', 'otherKey' => 'id'];
    }
}
