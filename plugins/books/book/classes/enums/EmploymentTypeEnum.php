<?php

namespace Books\Book\Classes\Enums;

/**
 * Тип занятости
 */
enum EmploymentTypeEnum: string
{
    case INDIVIDUAL = 'individual';
    case ENTERPRENEUR = 'enterpreneur';
    case SELF_EMPLOYEED = 'self_employeed';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Физическое лицо',
            self::ENTERPRENEUR => 'Индвидуальный предприниматель',
            self::SELF_EMPLOYEED => 'Самозанятый',
        };
    }
}
