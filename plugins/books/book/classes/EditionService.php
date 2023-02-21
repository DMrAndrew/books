<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Models\Edition;
use Event;

class EditionService
{
    public function __construct(protected Edition $edition)
    {

    }

    public function update(array $payload)
    {
        $data = collect($payload)->only(['price', 'status', 'free_parts', 'sales_free']);
        if ($status = BookStatus::tryFrom($data->get('status')) ?? false) {
            $data['status'] = $status;
        } else {
            $data->forget('status');
        }
        if ($data->has('status') && !in_array($data->get('status'), $this->edition->getAllowedStatusCases())) {
            throw new \ValidationException(['status' => 'В данный момент Вы не можете перевести издание в этот статус.']);
        }

        if (!$this->edition->isPublished()
            && in_array($data->get('status'), [BookStatus::COMPLETE, BookStatus::WORKING])
            && ($data->get('sales_free') == 'on' || ($data->has('price') && $data->has('free_parts')))
        ) {
            $this->edition->setPublishAt();
        }

        $this->edition->addValidationRule('free_parts', 'min:3');
        $this->edition->fill($data->toArray());

        $this->edition->save();
        Event::fire('books.edition.updated', [$this->edition]);
    }

    public function changeChaptersOrder(array $sequence)
    {
        if ($this->edition->editAllowed() || $this->edition->status === BookStatus::FROZEN) {
            $this->edition->changeChaptersOrder($sequence);
            Event::fire('books.edition.chapters.order.updated', [$this->edition]);
        }
        throw new \ValidationException(['chapters' => 'В данный момент Вы не можете изменить порядок частей.']);

    }
}
