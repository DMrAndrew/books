<?php namespace Books\Blacklists\Models;

use Model;
use October\Rain\Database\Traits\Validation;

/**
 * ChatBlacklist Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class ChatBlacklist extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_blacklists_chat_blacklists';

    /**
     * @var array
     */
    public $fillable = [
        'owner_profile_id',
        'banned_profile_id',
    ];

    /**
     * @var array
     */
    public $rules = [
        'owner_profile_id' => 'required|exists:books_profile_profiles,id',
        'banned_profile_id' => 'required|exists:books_profile_profiles,id',
    ];
}
