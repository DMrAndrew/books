<?php
declare(strict_types=1);

namespace Books\Profile\Services;

use Books\Book\Models\Author;
use Books\Book\Models\AwardBook;
use Books\Book\Models\Book;
use Books\Orders\Models\Order;
use Books\Profile\Contracts\OperationHistoryService as OperationHistoryServiceContract;
use Books\Profile\Classes\Enums\OperationType;
use Books\Profile\Models\OperationHistory;
use RainLab\User\Models\User;

class OperationHistoryService implements OperationHistoryServiceContract
{
    /**
     * @param OperationHistory $operation
     *
     * @return string|null
     */
    public function formatMessageByType(OperationHistory $operation): ?string
    {
        $metadata = $operation->metadata;

        switch($operation->type) {

            /**
             * Пополнение баланса
             */
            case OperationType::TransferOnBalance:
            case OperationType::DepositOnBalance:
                if (!$metadata['amount']) {
                    return null;
                }

                return "Баланс пополнен на {$metadata['amount']} ₽";

            /**
             * Получение на баланс
             */

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
             * Поддержка автора (оплата)
             */
            case OperationType::SupportMake:
                if (!$metadata['from'] || !$metadata['amount']) {
                    return null;
                }

                $user = User::find($metadata['from']);
                $url = url('author-page', ['profile_id' => $user->id]);
                $name = $user->username;

                return <<<FORMATTED
                    Вы поддержали автора
                    <a href="{$url}" class="ui-link _violet">
                        &laquo;$name&raquo;
                    </a>
                    на сумму {$metadata['amount']} ₽ от
                    FORMATTED;

            /**
             * Поддержка автора (получение)
             */
            case OperationType::SupportReceive:
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

            /**
             * Награда (оплата)
             */
            case OperationType::RewardMake:
                if (!$metadata['book_id'] || !$metadata['amount']) {
                    return null;
                }

                $book = Book::find($metadata['book_id']);
                $url = url('book-card', ['book_id' => $metadata['book_id']]);
                $bookName = $book->title;

                return <<<FORMATTED
                    Вы наградили книгу
                    <a href="{$url}" class="ui-link _violet">
                        &laquo;{$bookName}&raquo;
                    </a>
                    на сумму {$metadata['amount']} ₽
                    FORMATTED;

            default:
                return $operation->message;
        }
    }

    /**
     * @param Order $order
     *
     * @return void
     */
    public function addBalanceDeposit(Order $order): void
    {
        $user = $order->user;
        $depositAmount = $order->deposits->sum('amount');

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::DepositOnBalance,
            'message' => "Баланс пополнен на {$depositAmount} ₽",
            'metadata' => [
                'amount' => $depositAmount,
            ],
        ]);
    }

    /**
     * @param Order $order
     *
     * @return void
     */
    public function addReceivingCertificateAnonymous(Order $order): void
    {
        $user = $order->user;
        $depositAmount = $order->deposits->sum('amount');

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::TransferOnBalance,
            'message' => "Баланс пополнен на {$depositAmount} ₽",
            'metadata' => [
                'amount' => $depositAmount,
            ],
        ]);
    }

    public function addWithdrawal(Order $order): void
    {
        // TODO: Implement addWithdrawal() method.
    }

    /**
     * @param Order $order
     * @param mixed $orderProduct
     *
     * @return void
     */
    public function addReceivingPurchase(Order $order, mixed $orderProduct): void
    {
        $user = $order->user;
        $product = $orderProduct->orderable;

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::Buy,
            'message' => "Куплено произведение {$product->name} на {$orderProduct->amount} ₽",
            'metadata' => [
                'edition_class' => $product::class,
                'edition_id' => $product->id,
                'amount' => $orderProduct->amount,
            ],
        ]);
    }

    /**
     * @param Order $order
     * @param mixed $orderProduct
     *
     * @return void
     */
    public function addReceivingSubscription(Order $order, mixed $orderProduct): void
    {
        $user = $order->user;
        $product = $orderProduct->orderable;

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::Subscribed,
            'message' => "Оформлена подписка на {$product->name} на {$orderProduct->amount} ₽",
            'metadata' => [
                'edition_class' => $product::class,
                'edition_id' => $product->id,
                'amount' => $orderProduct->amount,
            ],
        ]);
    }

    /**
     * @param Order $order
     * @param Author $author
     *
     * @return void
     */
    public function addMakingAuthorSupport(Order $order, Author $author): void
    {
        $user = $order->user;
        $donationAmount = $order->donations->sum('amount');

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::SupportMake,
            'message' => "Вы поддержали автора {$author->profile->username} на сумму {$donationAmount} ₽",
            'metadata' => [
                'author_id' => $author->id,
                'amount' => $donationAmount,
            ],
        ]);
    }

    /**
     * @param Order $order
     * @param Author $author
     *
     * @return void
     */
    public function addReceivingAuthorSupport(Order $order, Author $author): void
    {
        $user = $order->user;
        $donationAmount = $order->donations->sum('amount');

        OperationHistory::create([
            'user_id' => $author->profile->user->id,
            'type' => OperationType::SupportReceive,
            'message' => "Вы получили {$donationAmount} ₽ от {$user->username}",
            'metadata' => [
                'user_id' => $user->id,
                'amount' => $donationAmount,
            ],
        ]);
    }

    public function addReceivingCertificatePublic(Order $order): void
    {
        // TODO: Implement addReceivingCertificatePublic() method.
    }

    /**
     * @param Order $order
     * @param AwardBook $awardBook
     *
     * @return void
     */
    public function addMakingAuthorReward(Order $order, AwardBook $awardBook): void
    {
        $user = $order->user;
        $award = $awardBook->award;
        $book = $awardBook->book;

        OperationHistory::create([
            'user_id' => $user->id,
            'type' => OperationType::RewardMake,
            'message' => "Вы наградили книгу {$book->name} на сумму {$award->price} ₽",
            'metadata' => [
                'book_id' => $book->id,
                'amount' => $award->price,
            ],
        ]);
    }
}
