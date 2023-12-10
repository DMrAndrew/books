<?php


namespace Books\Certificates\Behaviors;

use Books\Certificates\Models\CertificateTransactions;
use Books\Profile\Models\Profile;
use October\Rain\Extension\ExtensionBase;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class CertificateRelations extends ExtensionBase
{
    use HasRelationships;

    public function __construct(protected Profile $user)
    {
        $this->user->hasMany['certificate_sender'] = [CertificateTransactions::class, 'key' => 'sender_id', 'otherKey' => 'id'];
        $this->user->hasMany['certificate_receiver'] = [CertificateTransactions::class, 'key' => 'recipient_id', 'otherKey' => 'id'];
    }
}
