<?php


namespace Books\Certificates\Behaviors;

use Books\Certificates\Models\CertificateTransactions;
use Books\Profile\Models\Profile;
use October\Rain\Extension\ExtensionBase;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class CertificateRelations extends ExtensionBase
{
    use HasRelationships;

    public function __construct(protected Profile $profile)
    {
        $this->profile->hasMany['sender'] = [CertificateTransactions::class, 'key' => 'sender_id', 'otherKey' => 'id'];
        $this->profile->hasMany['receiver'] = [CertificateTransactions::class, 'key' => 'recipient_id', 'otherKey' => 'id'];
    }
}
