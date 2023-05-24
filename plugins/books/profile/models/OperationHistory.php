<?php namespace Books\Profile\Models;

use Books\Notifications\Classes\Contracts\NotificationService;
use Books\Profile\Classes\Enums\OperationType;
use Books\Profile\Contracts\OperationHistoryService;
use Model;
use RainLab\User\Models\User;

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
        'metadata' => 'sometimes|string',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'metadata',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'type' => OperationType::class,
        'metadata' => 'json',
    ];

    /**
     * @return string
     */
    public function formattedByType(): string
    {
        $service = app(OperationHistoryService::class);

        try{
            return $service->formatMessageByType($this) ?? $this->message;
        } catch (\Exception $e) {
            return $this->message;
        }
    }

}
