<?php

namespace Books\Notifications\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use RainLab\Notify\Models\Notification as Notify;
use RainLab\User\Models\User;

/**
 * Notification Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Notification extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_notifications_notifications';

    protected $fillable = ['read_at', 'notify_id', 'user_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $hasOne = [
        'notify' => [Notify::class],
        'user' => [User::class],
    ];
}
