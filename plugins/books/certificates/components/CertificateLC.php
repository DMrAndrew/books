<?php namespace Books\Certificates\Components;

use ApplicationException;
use Books\Certificates\Classes\Enums\CertificateTransactionStatus;
use Books\Certificates\Models\CertificateTransactions;
use Books\Profile\Models\Profile;
use Books\Profile\Services\OperationHistoryService;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Support\Facades\Cookie;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;

/**
 * CertificateLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CertificateLC extends ComponentBase
{
    private $user;
    /**
     * @var OperationHistoryService|\Illuminate\Contracts\Foundation\Application|mixed
     */
    private mixed $operationHistoryService;

    public function componentDetails()
    {
        return [
            'name' => 'CertificateLC Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->user = Auth::getUser() ?? throw new ApplicationException('User required');
        $this->operationHistoryService = app(OperationHistoryService::class);
    }

    public function onRun()
    {
        $this->page['sender_id'] = $this->user->getKey();
        $this->page['user_amount'] = $this->user->proxyWallet()->balance;
    }

    public function onSearchAuthor()
    {
        try {
            $name = post('term');
            if (!$name && strlen($name) < 1) {
                return [];
            }

            $array = Profile::searchByString($name)?->get()?->diff($this->user->profiles()->get());

            return $array->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->username,
                    'htm' => $this->renderPartial('select/option', ['label' => $item->username]),
                    'handler' => $this->alias . '::onSaveRecipient',

                ];
            })->toArray();
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
        return [];
    }

    public function onSave()
    {
        try {
            $sender = Profile::where('id', post('sender_id'))->first();
            $receiver = Profile::where('id', post('recipient_id'))->first();
            $amount = (int)post('amount');

            if ($sender->getKey() !== $receiver->getKey() && $sender->user->proxyWallet()->balance > $amount) {
                $sender->user->proxyWallet()->withdraw($amount);
                CertificateTransactions::create([
                    'sender_id' => post('sender_id'),
                    'recipient_id' => post('recipient_id'),
                    'amount' => $amount,
                    'description' => post('description'),
                    'anonymity' => (boolean)post('anonymity'),
                    'status' => CertificateTransactionStatus::SENT
                ]);
                $this->operationHistoryService->sentCertificate($sender, $amount, $receiver);

                Flash::success('Сертификат успешно оформлен');

                return Redirect::refresh();
            }

        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }
    }

    public function onSaveRecipient()
    {
        return [
            '#recipient_value' => $this->renderPartial('@recipient_input', [
                'recipient_id' => post('item.id')
            ])
        ];
    }
}
