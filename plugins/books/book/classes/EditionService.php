<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Models\Edition;
use Event;
use Illuminate\Support\Collection;
use ValidationException;

class EditionService
{
    public function __construct(protected Edition $edition)
    {
    }

    public function update(array $payload)
    {
        $data = collect($payload)->only(['price', 'status', 'free_parts']);
        $this->edition->fill($data->toArray());

        if ($status = BookStatus::tryFrom($data->get('status')) ?? false) {
            $data['status'] = $status;
        } else {
            $data->forget('status');
        }

        if ($this->edition->isDirty(['status']) && ! in_array($this->edition->status, $this->edition->getAllowedStatusCases())) {
            throw new ValidationException(['status' => 'В данный момент Вы не можете перевести издание в этот статус.']);
        }
        if ($this->edition->isDirty(['free_parts']) && ! $this->edition->editAllowed()) {
            throw new ValidationException(['edition' => 'Для этой книги запрещено редактирование продаж.']);
        }

        if ($this->edition->price) {
            $this->edition->addValidationRule('free_parts', 'min:3');
        }

        if (! $this->edition->isPublished()
            && in_array($data->get('status'), [BookStatus::COMPLETE, BookStatus::WORKING])
            && ($data->get('sales_free') == 'on' || ($data->has('price') && $data->has('free_parts')))
        ) {
            $this->edition->setPublishAt();
        }

        $this->fireEvents($data);
        $this->edition->save();
        Event::fire('books.edition.updated', [$this->edition]);
    }

    /**
     * @throws ValidationException
     */
    public function changeChaptersOrder(array $sequence)
    {
        if (! $this->edition->editAllowed()) {
            throw new ValidationException(['chapters' => 'В данный момент Вы не можете изменить порядок частей.']);
        }
        $this->edition->changeChaptersOrder($sequence);
        Event::fire('books.edition.chapters.order.updated', [$this->edition]);
    }

    /**
     * @param  Collection  $data
     * @return void
     */
    private function fireEvents(Collection $data): void
    {
        // книга была в статусе "Скрыта" перешла в "В работе" или "Завершена"
        if (
            $this->edition->isDirty(['status']) &&
            $this->edition->getOriginal('status') === BookStatus::HIDDEN &&
            in_array($data->get('status'), [BookStatus::COMPLETE, BookStatus::WORKING], true)
        ) {
            Event::fire('books.book::book.created', [$this->edition->book]);
        }

        // у электронной книги статус "В работе" перешел в "Завершена"
        if (
            $this->edition->isDirty(['status']) &&
            $this->edition->getOriginal('status') === BookStatus::WORKING &&
            $data->get('status') === BookStatus::COMPLETE
        ) {
            Event::fire('books.book::book.completed', [$this->edition->book]);
        }

        // у книги сменился статус и стоимость
        if ($this->edition->isDirty(['status', 'price'])) {
            // если перешла в статус "В работе" значит подписка, если "Завершено" значит продажа
            if ($data->get('status') === BookStatus::WORKING) {
                Event::fire('books.book::book.selling.subs', [$this->edition->book]);
            } elseif ($data->get('status') === BookStatus::COMPLETE) {
                Event::fire('books.book::book.selling.full', [$this->edition->book]);
            }
        }
    }
}
