<?php namespace Books\Certificates\Components;

use ApplicationException;
use Books\Certificates\Classes\Enums\CertificateTransactionStatus;
use Books\Certificates\Models\CertificateTransactions;
use Books\Profile\Services\OperationHistoryService;
use Cms\Classes\ComponentBase;
use Cookie;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use Redirect;

/**
 * CertificateModal Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CertificateModal extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'CertificateModal Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser() ?? throw new ApplicationException('User required');
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onShowCertificateModal()
    {
        $certificate = CertificateTransactions::where('id', post('certificate_id'))->first();
        $data['#certificate-modal'] = $this->renderPartial('@modal', [
            'certificate' => $certificate
        ]);
        return $data;
    }

    public function onGetCertificate()
    {
        try {
            $certificate = CertificateTransactions::where('id', post('certificate_id'))->first();
            $certificate->receiver->user->proxyWallet()->deposit($certificate->amount);
            $operationHistoryService = app(OperationHistoryService::class);
            $operationHistoryService->addReceivingCertificateAnonymous($certificate->receiver->user, (int)$certificate->amount);
            $certificate->status = CertificateTransactionStatus::RECEIVED;
            $certificate->save();
            Flash::success('Баланс пополнен');
            return Redirect::refresh();
        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }
    }
}
