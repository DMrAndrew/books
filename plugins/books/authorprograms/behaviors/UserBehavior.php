<?php

namespace Books\AuthorPrograms\behaviors;

use Books\AuthorPrograms\Classes\Enums\ProgramsEnums;
use Books\AuthorPrograms\Models\AuthorsPrograms;
use October\Rain\Database\Builder;
use October\Rain\Database\QueryBuilder;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class UserBehavior extends ExtensionBase
{
    use HasRelationships;

    public function __construct(protected User $user)
    {
        $this->user->hasMany['programs'] = [AuthorsPrograms::class, 'key' => 'user_id', 'otherKey' => 'id'];
    }
}
