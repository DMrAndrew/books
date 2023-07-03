<?php
declare(strict_types=1);

namespace Books\Withdrawal\Classes\Services;

use Books\Withdrawal\Classes\Contracts\AgreementServiceContract;
use Books\Withdrawal\Models\WithdrawalData;
use Mail;
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
        return 'HTML Договора';
    }

    public function getAgreementPDF(): string
    {
        return 'PDF Договора';
    }

    public function sendVerificationCode(): void
    {
        $email = $this->user->email;
        $code = $this->generateVerificationCode();

        $data = [
            'name' => $this->user->username,
            'email' => $email,
            'verificationUrl' => "/lc-commercial-withdraw/verify?code={$code}",
            'code' => $code,
        ];

        // пользователю
        Mail::queue(
            'books.withdrawal::mail.agreement_verify',
            $data,
            fn($msg) => $msg->to($email)
        );

        // todo: копия админу?
    }

    public function verifyAgreement(): void
    {
        // TODO: Implement verifyAgreement() method.
    }

    /**
     * @return string
     */
    private function generateVerificationCode(): string
    {
        $withdrawal = $this->user->withdrawalData;

        $code = WithdrawalData::generateCode();
        $withdrawal->update([
            'approve_code' => $code,
        ]);

        return $code;
    }
}
