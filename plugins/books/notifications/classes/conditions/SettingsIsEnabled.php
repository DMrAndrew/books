<?php

namespace Books\Notifications\Classes\Conditions;

use Books\User\Classes\UserSettingsEnum;
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

    public function getText()
    {
        $result = '';
        $host = $this->host;
        $setting = UserSettingsEnum::tryFrom($host->subcondition);
        if ($setting) {
            $value = $setting->optionEnum($host->value);
            $result = '"'.$setting->label().'"';
            $operator = mb_strtolower(array_get($this->getOperatorOptions(), $host->operator, $host->operator));
            $result .= ' <span class="operator">'.$operator.'</span> ';
            $result .= '"'.$value?->label().'"' ?? '';
        }

        return $result;
    }

    public function getName()
    {
        return 'Настройка пользователя';
    }

    public function getGroupingTitle()
    {
        return 'Настройки пользователя';
    }

    public function listSubconditions()
    {
        return array_flip($this->getSubconditionOptions());
    }

    public function getSubconditionOptions()
    {
        $options = [];
        foreach (UserSettingsEnum::cases() as $setting) {
            $options[$setting->value] = $setting->label();
        }

        return $options;
    }

    public function getValueOptions()
    {
        $opt = [];
        if (! UserSettingsEnum::tryFrom($this->host->subcondition)) {
            return $opt;
        }
        foreach (UserSettingsEnum::tryFrom($this->host->subcondition)?->options() ?? [] as $option) {
            $opt[$option->value] = $option->label();
        }

        return $opt;
    }

    public function getOperatorOptions()
    {
        return [
            'is' => 'Соответствует',
            'not' => 'Не соответствует',
        ];
    }

    public function initConfigData($host)
    {
        $first = UserSettingsEnum::tryFrom($this->host->subcondition) ?? UserSettingsEnum::cases()[0] ?? null;
        $host->subcondition = $first?->value ?? null;
        $host->operator = 'is';
        $host->value = $first?->defaultOption()?->value ?? null;
    }

    public function defineFormFields()
    {
        return '$/books/notifications/classes/conditions/setting_fields.yaml';
    }

    public function getTitle()
    {
        return 'Настройки пользователя';
    }

    /**
     * @param $params
     *
     * $params[recipients] - содержит Profile модели получателей.
     * @return bool|int
     */
    public function isTrue(&$params)
    {
        $recipients = collect($params['recipients'] ?? []);
        $setting = UserSettingsEnum::tryFrom($this->host->subcondition);
        $value = $setting->optionEnum($this->host->value);
        if (! $setting || ! $value) {
            return false;
        }
        //TODO N+1
        $params['recipients'] = $recipients->filter(function ($profile) use ($setting, $value) {
            $operand = $this->host->operator === 'is' ? 'exists' : 'notExists';

            return $profile->settings()
                ->type([$setting])
                ->value([$value])
                ->{$operand}();
        });

        return count($params['recipients']);
    }
}
