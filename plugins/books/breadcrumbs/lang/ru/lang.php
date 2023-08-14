<?php

return [
    'plugin'   => [
        'name'        => 'Хлебные крошки',
        'description' => 'Отображение хлебных крошек на странице',
        'permissions' => [
            'access_settings' => 'Доступ к настройке хлебных крошек',
        ],
    ],
    'settings' => [
        'label'       => 'Хлебные крошки',
        'description' => 'Настройки хлебных крошек',
        'homepage'    => 'Главная страница',
        'catalog'     => 'Страница каталога',
        'lc'            => 'Личный кабинет',
        'commercial_cabinet'     => 'Коммерческий кабинет',
        'no_select'   => '-- Не выбрано --',
    ],
    'component' => [
        'name'        => 'Хлебные крошки',
        'description' => 'Отображение хлебных крошек на странице',
    ],
    'exceptions' => [
        'duplicate' => 'Имя крошки уже зарегистрировано :name',
        'invalid'   => 'Крошка не найдена с именем :name',
    ],
];
