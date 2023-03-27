<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Models\Edition;
use Event;
use ValidationException;

class EditionService
{
    public function __construct(protected Edition $edition)
    {
    }

    public function update(array $payload)
    {
        $data = collect($payload)->only(['price', 'status', 'free_parts', 'sales_free']);
        $this->edition->fill($data->toArray());

        if ($this->edition->isDirty(['price', 'free_parts', 'sales_free']) && ! $this->edition->editAllowed()) {
            throw new ValidationException(['edition' => 'Для этой книги запрещено редактирование продаж.']);
        }

        if ($status = BookStatus::tryFrom($data->get('status')) ?? false) {
            $data['status'] = $status;
        } else {
            $data->forget('status');
        }

        if ($data->has('status') && ! in_array($data->get('status'), $this->edition->getAllowedStatusCases())) {
            throw new ValidationException(['status' => 'В данный момент Вы не можете перевести издание в этот статус.']);
        }

        if ($data->get('sales_free') == 0 && ($data->has('price') && ! (bool) $data->get('price'))) {
            $this->edition->addValidationRule('free_parts', 'min:3');
        }

        if (! $this->edition->isPublished()
            && in_array($data->get('status'), [BookStatus::COMPLETE, BookStatus::WORKING])
            && ($data->get('sales_free') == 'on' || ($data->has('price') && $data->has('free_parts')))
        ) {
            $this->edition->setPublishAt();
        }
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
}
