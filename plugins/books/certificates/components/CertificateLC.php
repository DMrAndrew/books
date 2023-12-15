<?php namespace Books\Certificates\Components;

use ApplicationException;
use Books\Certificates\Classes\Enums\CertificateTransactionStatus;
use Books\Certificates\Models\CertificateTransactions;
use Books\FileUploader\Components\FileUploader;
use Books\FileUploader\Components\ImageUploader;
use Books\Profile\Models\Profile;
use Books\Profile\Services\OperationHistoryService;
use Cms\Classes\ComponentBase;
use Event;
use Exception;
use Flash;
use Illuminate\Support\Facades\Cookie;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;
use ValidationException;
use Validator;

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
        $component = $this->addComponent(
            ImageUploader::class,
            'certificateUploader',
            [
                'modelClass' => CertificateTransactions::class,
                'modelKeyColumn' => 'image',
                'deferredBinding' => true,
                'imageWidth' => 150,
                'imageHeight' => 150,

            ]
        );
        $component->bindModel('certificate_image', new CertificateTransactions());
    }

    public function onRun()
    {
        $this->page['sender_id'] = $this->user->profile->id;
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
                    'label' => $item->username . " (id: $item->id)",
                    'htm' => $this->renderPartial('select/option', ['label' => $item->username . " (id: $item->id)"]),
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

    public function getSessionKey()
    {
        return post('_session_key');
    }

    public function onSave()
    {
        try {
            $postData = collect(post());

            $validator = Validator::make(
                $postData->toArray(),
                collect((new CertificateTransactions())->rules)->only([
                    'recipient_id', 'amount', 'description'
                ])->toArray(),
                collect((new CertificateTransactions())->customMessages)->only([
                    'recipient_id', 'amount', 'description'
                ])->toArray(),
                collect((new CertificateTransactions())->attributeNames)->only([
                    'recipient_id', 'amount', 'description'
                ])->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            $sender = Profile::where('id', $postData['sender_id'])->first();
            $receiver = Profile::where('id', $postData['recipient_id'])->first();
            $amount = (int)$postData['amount'];
            $anonymity = (boolean)$postData['anonymity'];

            if ($sender->getKey() !== $receiver->getKey() && (int)$sender->user->proxyWallet()->balance > $amount) {
                $sender->user->proxyWallet()->withdraw($amount);
                $data = [
                    'sender_id' => $postData['sender_id'],
                    'recipient_id' => $postData['recipient_id'],
                    'amount' => $amount,
                    'description' => $postData['description'],
                    'anonymity' => $anonymity,
                    'status' => CertificateTransactionStatus::SENT
                ];
                $certificate = new CertificateTransactions();
                $certificate->fill($data)->save();
                $certificate->save(null, $this->getSessionKey());
                $this->operationHistoryService->sentCertificate($sender->user, $amount, $receiver);

                Event::fire('system::certificate', [$amount, $receiver, $anonymity, $sender, $certificate->id]);

                Flash::success('Сертификат успешно оформлен');
                return Redirect::to('/');
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
