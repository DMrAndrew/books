<?php namespace Books\AuthorPrograms\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use October\Rain\Support\Facades\Event;

/**
 * AuythorBeforeBirsdayNotificationCommand Command
 *
 * @link https://docs.octobercms.com/3.x/extend/console-commands.html
 */
class AuthorBirthdayNotificationCommand extends Command
{
    /**
     * @var string signature for the console command.
     */
    protected $signature = 'authorprograms:birthday_notification';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Оповещение автора за 3 дня до ДР что у него не будет коммисии';

    /**
     * handle executes the console command.
     */
    public function handle()
    {
        Event::fire('system::birthday');
    }
}
