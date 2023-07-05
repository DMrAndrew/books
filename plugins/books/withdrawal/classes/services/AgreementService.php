<?php
declare(strict_types=1);

namespace Books\Withdrawal\Classes\Services;

use Backend;
use Books\Withdrawal\Classes\Contracts\AgreementServiceContract;
use Books\Withdrawal\Classes\Enums\WithdrawalAgreementStatusEnum;
use Books\Withdrawal\Models\WithdrawalData;
use Exception;
use Log;
use Mail;
use Mpdf\Mpdf as HTMLtoPDFConverter;
use Mpdf\MpdfException;
use RainLab\User\Models\User;
use Backend\Models\User as AdminUser;

class AgreementService implements AgreementServiceContract
{
    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param bool $verified
     *
     * @return string
     */
    public function getAgreementHTML(bool $verified = false): string
    {
        $withdrawal = $this->user->withdrawalData;

        $heading = !$verified ? 'Заявление' : 'Договор';
        $agreementDate = $withdrawal->approved_at->format('«d» F Y г.'); //«05» июль 2023 г.
        $offerUrl = url('/terms-of-use');
        $termsOfUseUrl = url('/privacy-agreement');

        $egrip = $withdrawal->employment_register_number ? 'ЕГРИП ' . $withdrawal->employment_register_number : '';
        $signCode = $verified ? '<b>'.$withdrawal->approve_code.'</b>' : '';

        return <<<AGREEMENT
            <div class="agreement">
            <div class="agreement-title ui-text-head--3 ui-text--bold">{$heading}</div>
            <div class="agreement-head">
            {$agreementDate}<br>
            г. Снежинск
            </div>
            <div class="agreement-body">
            Я, {$withdrawal->fio}, ознакомился(лась) и согласен(сна) с <a href="{$offerUrl}" target="_blank">офертой сайта</a>, документооборотом, <a href="{$termsOfUseUrl}" target="_blank">соглашением конфиденциальности</a>. Прошу разрешить мне вывод средств на указанный в анкете счет. Гарантирую законность моего авторского контента и уведомлен(а) об ответственности за нарушение авторского права. Даю согласие на обработку своих персональных данных. Гарантирую соблюдение правил сайта, размещенных в открытом доступе.
            <table class="agreement-data">
              <tbody>
                <tr>
                  <td>Данные пользователя</td>
                  <td>Реквизиты счета</td>
                </tr>
                <tr>
                  <td>{$withdrawal->fio}<br>
                    Дата рождения {$withdrawal->birthday->format('d.m.Y')}<br>
                    {$withdrawal->employment_type->getLabel()} ИНН {$withdrawal->inn} {$egrip}<br>
                    Паспорт {$withdrawal->passport_number}, выдан {$withdrawal->passport_issued_by}, дата выдачи {$withdrawal->passport_date->format('d.m.Y')}<br>
                    Зарегистрирован(на) по адресу {$withdrawal->address}<br>
                    <br>
            Электронный адрес: {$withdrawal->email}
            </td>
                  <td>
            ИНН Банка: {$withdrawal->bank_inn}<br>
            КПП Банка: {$withdrawal->bank_kpp}<br>
            Получатель: {$withdrawal->bank_receiver}<br>
            Номер счета: {$withdrawal->bank_account}<br>
            БИК: {$withdrawal->bank_bik}<br>
            Банк получатель: {$withdrawal->bank_beneficiary}<br>
            Корр. Счет: {$withdrawal->bank_corr_account}
                  </td>
                </tr>
              </tbody>
            </table>
            Подтверждаю правильность всех моих данных и обязуюсь обновлять их в случае изменений.<br>
            Подпись: {$signCode}
            </div>
            </div>
        AGREEMENT;
    }

    /**
     * @return string
     * @throws MpdfException
     */
    public function getAgreementPDF(): string
    {
        if ($this->user->withdrawalData->agreement_status != WithdrawalAgreementStatusEnum::APPROVED) {
            throw new Exception('Невозможно скачать договор - договор не подписан или не подтвержден администрацией сайта');
        }

        $agreementHTMLBody = $this->getAgreementHTML($verified = true);
        $agreementCSS = "<style>"
            . file_get_contents(base_path() . '/themes/demo/assets/css/main.min.css')
            . "</style>";

        $mpdf = new HTMLtoPDFConverter(['tempDir' => storage_path('/temp/agreement-downloads')]);
        $mpdf->WriteHTML($agreementCSS . $agreementHTMLBody);

        return $mpdf->Output();
    }

    /**
     * @return void
     */
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
