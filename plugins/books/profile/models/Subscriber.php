<?php namespace Books\Profile\Models;

use Model;
use October\Rain\Database\Traits\Validation;

/**
 * Subscriber Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Subscriber extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_profile_subscribers';

    protected $fillable = ['subscriber_id','profile_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'profile_id' => 'required|exists:books_profile_profiles,id',
        'subscriber_id' => 'required|exists:books_profile_profiles,id',
    ];

}
