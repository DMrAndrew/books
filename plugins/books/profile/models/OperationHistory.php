<?php namespace Books\Profile\Models;

use Books\Profile\Classes\Enums\OperationType;
use Model;

/**
 * OperationHistory Model
 *
 * @link https://bookstime.atlassian.net/wiki/spaces/books/pages/884841
 */
class OperationHistory extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_profile_operation_histories';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'user_id' => 'required|integer',
        'type' => 'required|integer',
        'message' => 'required|string',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'message',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'type' => OperationType::class,
    ];
}
