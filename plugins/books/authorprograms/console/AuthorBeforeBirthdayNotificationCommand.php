<?php namespace Books\AuthorPrograms\Console;

use Books\Profile\Models\Profile;
use Event;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use RainLab\User\Models\User;

/**
 * AuythorBeforeBirsdayNotificationCommand Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class AuthorBeforeBirthdayNotificationCommand extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'authorprograms:before_birthday_notification';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Оповещение автора за 3 дня до ДР что у него не будет коммисии';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        $object = \Books\Book\Models\SystemMessage::where('name', 'День рождения автора')->first();

        $users = User::leftJoin('books_profile_profiles as profile', 'users.id', '=', 'profile.id')
            ->leftJoin('books_book_authors as author', 'author.profile_id', '=', 'profile.id')
            ->whereMonth('users.birthday', Carbon::now()->addDays(3)->format('m'))
            ->whereDay('users.birthday', Carbon::now()->addDays(3)->format('d'))
            ->distinct()
            ->get('users.id');

        Event::fire('system::message', [$object, $users]);
    }
}
