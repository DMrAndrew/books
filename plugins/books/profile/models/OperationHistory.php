<?php namespace Books\Profile\Models;

use Books\Profile\Classes\Enums\OperationType;
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
        try{
            return $this->formatMessageByType() ?? $this->message;
        } catch (\Exception $e) {
            return $this->message;
        }
    }

    /**
     * @return string|null
     */
    private function formatMessageByType(): ?string
    {
        $metadata = $this->metadata;

        switch($this->type) {

            /**
             * Пополнение баланса
             */
            case OperationType::DepositOnBalance:
                if (!$metadata['amount']) {
                    return null;
                }

                return "Зачисление на баланс {$metadata['amount']} ₽";

            /**
             * Получение на баланс
             */
            case OperationType::TransferOnBalance:
                if (!$metadata['amount']) {
                    return null;
                }

                return "Баланс пополнен на {$metadata['amount']} ₽";

            /**
             * Покупка книги
             */
            case OperationType::Buy:
                if (!$metadata['edition_class'] ||  !$metadata['edition_id'] || !$metadata['amount']) {
                    return null;
                }
                $edition = $metadata['edition_class']::find($metadata['edition_id']);
                $name = $edition->book->title;
                $url = url('book-card', ['book_id' => $edition->book->id]);

                return <<<FORMATTED
                    Куплено произведение
                    <a href="{$url}" class="ui-link _violet">
                        &laquo;$name&raquo;
                    </a>
                    за {$metadata['amount']} ₽
                    FORMATTED;

            /**
             * Подписка
             */
            case OperationType::Subscribed:
                if (!$metadata['edition_class'] || !$metadata['edition_id'] || !$metadata['amount']) {
                    return null;
                }
                $edition = $metadata['edition_class']::find($metadata['edition_id']);
                $name = $edition->book->title;
                $url = url('book-card', ['book_id' => $edition->book->id]);

                return <<<FORMATTED
                    Оформлена подписка на книгу
                    <a href="{$url}" class="ui-link _violet">
                        &laquo;$name&raquo;
                    </a>
                    за {$metadata['amount']} ₽
                    FORMATTED;

            /**
             * Вывод средств
             */
            case OperationType::Withdraw:
                if (!$metadata['withdraw_amount'] ||  !$metadata['withdraw_total']) {
                    return null;
                }

                return <<<FORMATTED
                    <span class="notification-menu__text _green">
                        Вывод средств: {$metadata['withdraw_amount']} ₽ из {$metadata['withdraw_total']} ₽ доступных вам средств
                    </span>
                    FORMATTED;

            /**
             * Поддержка автора
             */
            case OperationType::Support:
                if (!$metadata['from'] || !$metadata['amount']) {
                    return null;
                }

                $user = User::find($metadata['from']);
                $url = url('author-page', ['profile_id' => $user->id]);
                $name = $user->username;

                return <<<FORMATTED
                    Вы получили {$metadata['amount']} ₽ от
                    <a href="{$url}" class="ui-link _violet">
                        &laquo;$name&raquo;
                    </a>
                    FORMATTED;

            default:
                return $this->message;
        }
    }
}
