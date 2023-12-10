<?php namespace Books\Certificates\Components;

use ApplicationException;
use Books\Certificates\Models\CertificateTransactions;
use Books\Profile\Services\OperationHistoryService;
use Cms\Classes\ComponentBase;
use Cookie;
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
        $this->operationHistoryService = app(OperationHistoryService::class);
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onShowModal()
    {
        if ($data['is_open'] = Cookie::has('show_certificate_modal')) {
            $certificate = $this->user->profile->certificate_receiver()->notAcceptedCertificates()->first();
            $data['#certificate-modal'] = $this->renderPartial('@modal', ['text' => $certificate->description]);
        }
        return $data;
    }

    public function onCloseModal()
    {
        Cookie::expire('show_certificate_modal');
    }

    public function onGetCertificate()
    {
        $certificate = $this->user->profile->certificate_receiver()->notAcceptedCertificates()->first();

        $this->user->proxyWallet()->deposit($certificate->amount);

        $this->operationHistoryService->addReceivingCertificateAnonymous($this->user, $certificate->amount, $receiver);

        return Redirect::refresh();
    }
}
