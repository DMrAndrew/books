<?php

namespace Books\Notifications\Classes\Actions;

use RainLab\Notify\Classes\ActionBase;

class BookBaseAction extends ActionBase
{
    /**
     * Returns information about this event, including name and description.
     */
    public function actionDetails()
    {
        return [
            'name' => 'Test Action',
            'description' => 'Some action',
            'icon' => 'icon-envelope',
        ];
    }

    public function defineFormFields()
    {
        return false;
    }

    public function getText()
    {
        return 'Sending a test message ';
    }

    /**
     * Triggers this action.
     *
     * @param $params
     *
     * $params['recipients'] - профили получателей
     * $params['body'] - сформированная ивентом data для уведомления
     * @return void
     */
    public function triggerAction($params)
    {
    }
}
