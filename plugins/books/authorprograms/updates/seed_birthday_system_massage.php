<?php

namespace Books\AuthorPrograms\Updates;

use October\Rain\Database\Updates\Seeder;
use Books\Book\Models\SystemMessage;

class seed_birthday_system_massage extends Seeder
{
    public function run()
    {
        SystemMessage::create([
            'name' => 'День рождения автора',
            'text' => '<p><strong data-renderer-mark="true"><u data-renderer-mark="true">“Автор”</u></strong>, в честь Вашего дня рождения мы дарим 100% от продажи книг (всего на 1 день). Разрекламируйте свои книги, чтобы получить как можно больше выгоды!</p>'
        ]);
    }
}
