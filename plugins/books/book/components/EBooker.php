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
        $this->page['ebook'] = $this->ebook->fresh();
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

    /**
     * @throws AjaxException
     */
    public function onUpdateSortOrder()
    {
        $partial = fn () => [
            '#ebooker-chapters' => $this->renderPartial('@chapters', ['ebook' => $this->ebook->fresh()]),
        ];

        try {
            $this->service->changeChaptersOrder(post('sequence'));

            return $partial();
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return $partial();
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
            Flash::error($ex->getMessage());
            $this->vals();

            return [
                '#ebook-settings' => $this->renderPartial('@settings'),
            ];
        }
    }
}
