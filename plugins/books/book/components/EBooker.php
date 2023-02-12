<?php

namespace Books\Book\Components;

use ApplicationException;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Models\Edition;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use Request;

/**
 * EBooker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class EBooker extends ComponentBase
{
    protected Edition $ebook;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'EBooker Component',
            'description' => 'No description provided yet...',
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $this->ebook = Auth::getUser()->profile->books()->find($this->property('book_id'))?->ebook;
        if (! $this->ebook) {
            throw new ApplicationException('Электронное издание книги не найден.');
        }
        $this->page['ebook'] = $this->ebook;
        $this->page['bookStatusCases'] = BookStatus::publicCases();
    }

    /**
     * defineProperties for the component
     *
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'book_id' => [
                'title' => 'Book',
                'description' => 'Книга пользователя',
                'type' => 'string',
                'default' => null,
            ],
        ];
    }

    public function onUpdateSortOrder()
    {
        try {
            $this->ebook->changeChaptersOrder(post('sequence'));

            return [
                '#ebooker-chapters' => $this->renderPartial('@chapters', ['ebook' => $this->ebook]),
            ];
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onUpdate()
    {
        try {
            $data = collect(post())->only(['price', 'status', 'free_parts', 'sales_free'])->toArray();
            $this->ebook->update($data);
            $this->ebook->setFreeParts();

            return [
                '#about-header' => $this->renderPartial('book/about-header', ['book' => $this->ebook->book]),
                '#ebooker-chapters' => $this->renderPartial('@chapters', ['ebook' => $this->ebook]),
                '#ebook-settings' => $this->renderPartial('@settings', ['ebook' => $this->ebook]),
            ];
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }
}
