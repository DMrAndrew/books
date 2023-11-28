<?php

namespace Books\Videoblog\Behaviors;

use Books\Blog\Models\Post;
use Books\Profile\Models\Profile;
use Books\Videoblog\Models\Videoblog;
use October\Rain\Extension\ExtensionBase;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class HasVideoBlog extends ExtensionBase
{
    use HasRelationships;

    public function __construct(protected Profile $profile)
    {
        $this->profile->hasMany['videoblog_posts'] = [Videoblog::class, 'key' => 'profile_id', 'otherKey' => 'id'];
    }
}
