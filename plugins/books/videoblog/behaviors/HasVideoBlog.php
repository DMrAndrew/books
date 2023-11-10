<?php

namespace Books\Blog\Behaviors;

use Books\Blog\Models\Post;
use Books\Profile\Models\Profile;
use October\Rain\Extension\ExtensionBase;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class HasVideoBlog extends ExtensionBase
{
    use HasRelationships;

    public function __construct(protected Profile $profile)
    {
        $this->profile->hasMany['posts'] = [Post::class, 'key' => 'profile_id', 'otherKey' => 'id'];
    }
}
