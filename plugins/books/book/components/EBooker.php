<?php

namespace Books\Book\Components;

use AjaxException;
use ApplicationException;
use Books\Book\Classes\EditionService;
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

    protected EditionService $service;

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
        $this->service = new EditionService($this->ebook);
    }

    public function onRun()
    {
        $this->vals();
    }

    public function vals()
    {
        $this->page['ebook'] = $this->ebook;
        $this->page['bookStatusCases'] = $this->ebook->getAllowedStatusCases();
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
            $this->service->changeChaptersOrder(post('sequence'));

            return [
                '#ebooker-chapters' => $this->renderPartial('@chapters', ['ebook' => $this->ebook->fresh()]),
            ];
        } catch (Exception $ex) {
            if (Request::ajax()) {
                Flash::error($ex->getMessage());
                throw new AjaxException([
                    '#ebooker-chapters' => $this->renderPartial('@chapters', ['ebook' => $this->ebook->fresh()]),
                ]);
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onUpdate()
    {
        try {
            $this->service->update(post());
            $this->ebook = $this->ebook->fresh();
            $this->vals();

            return [
                '#about-header' => $this->renderPartial('book/about-header'),
                '#ebooker-chapters' => $this->renderPartial('@chapters'),
                '#ebook-settings' => $this->renderPartial('@settings'),
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
