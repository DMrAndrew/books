<?php

namespace Books\Shop\Behaviors;

use Books\Profile\Models\Profile;
use Books\Shop\Models\Product;
use October\Rain\Extension\ExtensionBase;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class HasProduct extends ExtensionBase
{
    use HasRelationships;

    public function __construct(protected Profile $profile)
    {
        $this->profile->hasMany['products'] = [Product::class, 'key' => 'seller_id', 'otherKey' => 'id'];
    }
}
