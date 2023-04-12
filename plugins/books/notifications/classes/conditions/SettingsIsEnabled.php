<?php

namespace Books\Notifications\Classes\Conditions;

use RainLab\Notify\Classes\ConditionBase;

class SettingsIsEnabled extends ConditionBase
{
    public function getConditionType()
    {
        // If the condition should appear for all events
        return ConditionBase::TYPE_ANY;

        // If the condition should appear only for some events
        return ConditionBase::TYPE_LOCAL;
    }

    public function getName()
    {
        return 'Настройка пользователя';
    }

    public function getMyconditionOptions()
    {
        return [
            'true' => 'TRUE',
            'false' => 'FALSE',
        ];
    }

    public function defineFormFields()
    {
        return '$/books/notifications/classes/conditions/setting_fields.yaml';
    }

    public function getTitle()
    {
        return 'My test condition';
    }

    public function getText()
    {
        return 'Настройка <span class="operator">is</span> ';
    }

    public function isTrue(&$params)
    {
        return true;
    }
}
