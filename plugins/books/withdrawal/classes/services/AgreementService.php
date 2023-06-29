<?php
declare(strict_types=1);

namespace Books\Withdrawal\Classes\Services;

use Books\Withdrawal\Classes\Contracts\AgreementServiceContract;
use RainLab\User\Models\User;

class AgreementService implements AgreementServiceContract
{
    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getAgreementHTML(): string
    {
        return 'HTML';
    }

    public function getAgreementPDF(): string
    {
        // TODO: Implement getAgreementPDF() method.
    }

    public function sendVerificationCode(): void
    {
        // TODO: Implement sendVerificationCode() method.
    }

    public function verifyAgreement(): void
    {
        // TODO: Implement verifyAgreement() method.
    }
}
