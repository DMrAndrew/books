<?php

namespace Books\Book\Components;

use ApplicationException;
use Books\Book\Classes\EditionService;
use Books\Book\Models\Edition;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use ValidationException;

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
        $this->vals();
        $this->service = new EditionService($this->ebook);
    }

    public function onRun()
    {
    }

    public function fresh()
    {
        $this->ebook = Auth::getUser()?->profile->books()->with('chapters')->find($this->property('book_id'))?->ebook;
        if (!$this->ebook) {
            throw new ApplicationException('Электронное издание книги не найден.');
        }
    }

    public function vals()
    {
        $this->fresh();

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
        $partial = fn() => [
            '#ebooker-chapters' => $this->renderPartial('@chapters', ['ebook' => $this->ebook]),
        ];

        try {
            $this->service->changeChaptersOrder(post('sequence'));
            $this->fresh();

            return $partial();
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return $partial();
        }
    }

    public function onDeleteChapter()
    {
        $partial = fn() => [
            '#ebooker-chapters' => $this->renderPartial('@chapters', ['ebook' => $this->ebook]),
        ];
        try {
            $chapter_id = post('chapter_id');
            if ($chapter = $this->ebook->chapters()->find($chapter_id)) {
                $chapter->service()->delete();
            }

            $this->fresh();

            return $partial();
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return $partial();
        }
    }

    /**
     * @throws ValidationException
     */
    public function onUpdate()
    {
        if (!$this->ebook->book->genres()->count()) {
            Flash::error('Добавьте книге хотя бы один жанр.');
            return [];
        }
        try {
            $this->service->update(post());

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
