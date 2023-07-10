<?php namespace Books\Book\Models;

use App\traits\HasUserScope;
use Model;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * UserBook Model - Наличие книг на аккаунте (купленных или приобретенных другим способом)
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class UserBook extends Model
{
    use Validation;
    use HasUserScope;

    /**
     * @var string table name
     */
    public $table = 'books_user_books';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'user_id' => 'required|integer',
    ];

    protected $fillable = [
        'user_id',
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [
            User::class,
        ],
    ];

    /**
     * @var array
     */
    public $morphTo = [
        'ownable' => []
    ];
}
