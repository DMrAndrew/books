<?php namespace Books\Certificates\Components;

use ApplicationException;
use Books\Profile\Models\Profile;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use Request;

/**
 * CertificateLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class CertificateLC extends ComponentBase
{
    private $user;

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
    }

    public function onRun()
    {
        $this->page['sender_id'] = $this->user->getKey();
    }

    public function onSearchAuthor()
    {
        try {
            $name = post('term');
            if (! $name && strlen($name) < 1) {
                return [];
            }

            $array = Profile::searchByString($name)?->get()?->diff($this->user->profiles()->get());

            return $array->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->username,
                    'htm' => $this->renderPartial('select/option', ['label' => $item->username]),
                    'handler' => $this->alias.'::onSaveRecipient',

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
        dd(post());
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
