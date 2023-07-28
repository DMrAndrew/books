<?php namespace Books\Blacklists\Models;

use Model;
use October\Rain\Database\Traits\Validation;

/**
 * CommentsBlacklist Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class CommentsBlacklist extends Model
{
    use Validation;

    /**
     * @var string
     */
    public $table = 'books_blacklists_comments_blacklists';

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
