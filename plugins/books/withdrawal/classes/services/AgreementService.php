<?php
declare(strict_types=1);

namespace Books\Withdrawal\Classes\Services;

use Backend;
use Books\Withdrawal\Classes\Contracts\AgreementServiceContract;
use Books\Withdrawal\Models\WithdrawalData;
use Exception;
use Log;
use Mail;
use RainLab\User\Models\User;
use Backend\Models\User as AdminUser;

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
            'verificationUrl' => "/lc-commercial-withdraw?verification_code={$code}",
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

    /**
     * @param string $code
     *
     * @return bool
     */
    public function verifyAgreement(string $code): bool
    {
        $withdrawal = $this->user->withdrawalData;

        if ($withdrawal->approve_code == $code) {
            $withdrawal->update([
                'agreement_status' => WithdrawalAgreementStatusEnum::CHECKING,
                'approved_at' => now(),
            ]);

            $this->notifyAdminWithdrawalVerified();

            return true;
        }

        return false;
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

    /**
     * @return void
     */
    private function notifyAdminWithdrawalVerified(): void
    {
        try {
            $admins = AdminUser::all();
            $adminEmails = $admins->pluck('email')->toArray();

            $data = [
                'name' => $this->user->username,
                'email' => $adminEmails,
                'moderationUrl' => Backend::url("books/withdrawal/withdrawal/update/{$this->user->withdrawalData->id}"),
            ];

            // пользователю
            Mail::queue(
                'books.withdrawal::mail.admin_agreement_verified',
                $data,
                fn($msg) => $msg->to($adminEmails)
            );
        }
        catch (Exception $ex) {
            Log::error($ex->getMessage());
        }
    }
}
