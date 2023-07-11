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

    /**
     * @throws ValidationException
     */
    public function update(array $payload): void
    {
        $data = collect($payload)->only(['price', 'status', 'free_parts']);

        if ($status = BookStatus::tryFrom($data->get('status') ?? '') ?? false) {
            $data['status'] = $status;
        } else {
            $data->forget('status');
        }

        $this->edition->fill($data->toArray());

        if ($this->edition->isDirty(['status']) && !in_array($this->edition->status, $this->edition->getAllowedStatusCases())) {
            throw new ValidationException(['status' => 'В данный момент Вы не можете перевести издание в статус `' . $this->edition->status->name . '`']);
        }
        if ($this->edition->isDirty(['free_parts','price']) && !$this->edition->sellsSettingsEditAllowed()) {
            throw new ValidationException(['edition' => 'Для книги запрещено редактирование продаж']);
        }

        if ($this->edition->price) {
            $this->edition->addValidationRule('free_parts', 'min:'.config('book.minimal_free_parts'));
            $this->edition->addValidationRule('price', 'min:' . config('book.minimal_price'));
        }

        if (!$this->edition->isPublished()
            && in_array($this->edition->status, [BookStatus::COMPLETE, BookStatus::WORKING])
        ) {
            $this->edition->setPublishAt();
        }

        $this->edition->save();
        $this->fireEvents($data);
        $this->edition->setFreeParts();
        Event::fire('books.edition.updated', [$this->edition]);
    }

    /**
     * @throws ValidationException
     */
    public function changeChaptersOrder(array $sequence)
    {
        if (!$this->edition->editAllowed()) {
            throw new ValidationException(['chapters' => 'Для книги запрещено изменение порядка частей']);
        }
        $this->edition->changeChaptersOrder($sequence);
        Event::fire('books.edition.chapters.order.updated', [$this->edition]);
    }

    /**
     * @param Collection $data
     * @return void
     */
    private function fireEvents(Collection $data): void
    {
        // книга была в статусе "Скрыта" перешла в "В работе" или "Завершена"
        if (
            $this->edition->wasChanged(['status']) &&
            $this->edition->getOriginal('status') === BookStatus::HIDDEN &&
            in_array($data->get('status'), [BookStatus::COMPLETE, BookStatus::WORKING], true)
        ) {
            Event::fire('books.book::book.created', [$this->edition->book]);
        }

        // у электронной книги статус "В работе" перешел в "Завершена"
        if (
            $this->edition->wasChanged(['status']) &&
            $this->edition->getOriginal('status') === BookStatus::WORKING &&
            $data->get('status') === BookStatus::COMPLETE
        ) {
            Event::fire('books.book::book.completed', [$this->edition->book]);
        }

        // у книги сменился статус и стоимость
        if ($this->edition->wasChanged(['status']) && (bool)$this->edition->price) {
            // если перешла в статус "В работе" значит подписка, если "Завершено" значит продажа
            if ($data->get('status') === BookStatus::WORKING) {
                Event::fire('books.book::book.selling.subs', [$this->edition->book]);
            } elseif ($data->get('status') === BookStatus::COMPLETE) {
                Event::fire('books.book::book.selling.full', [$this->edition->book]);
            }
        }
    }
}
