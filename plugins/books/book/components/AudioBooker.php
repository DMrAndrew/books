<?php namespace Books\Book\Components;

use App\classes\PartialSpawns;
use ApplicationException;
use Books\Book\Classes\EditionService;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Models\Edition;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;

/**
 * AudioBooker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AudioBooker extends ComponentBase
{
    protected ?Edition $audiobook;

    protected EditionService $service;

    public function componentDetails()
    {
        return [
            'name' => 'AudioBooker Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'book_id' => [
                'title' => 'AudioBook',
                'description' => 'Аудиокнига пользователя',
                'type' => 'string',
                'default' => null,
            ],
        ];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }

        $this->vals();
        $this->setAllowedStatuses();
        $this->service = new EditionService($this->audiobook);
    }

    public function vals()
    {
        $this->fresh();
        $this->page['audiobook'] = $this->audiobook;
    }

    /**
     * @throws ApplicationException
     */
    public function fresh()
    {
        $this->audiobook = Auth::getUser()?->profile
            ->books()
            ->with(['audiobook.chapters' => fn($chapters) => $chapters->with(['deferred' => fn($d) => $d->deferred()])])
            ->find($this->property('book_id'))?->audiobook
            ?? new Edition([
                'type' => EditionsEnums::Audio,
                'status' => BookStatus::HIDDEN,
                'book_id' => $this->property('book_id'),
            ]);
    }

    protected function renderChapters()
    {
        return [
            '#audiobooker-chapters' => $this->renderPartial('@chapters', ['audiobook' => $this->audiobook]),
        ];
    }

    protected function setAllowedStatuses()
    {
        $this->page['bookStatusCases'] = $this->audiobook->getAllowedStatusCases();
    }

    public function onDeleteChapter(): array
    {
        try {
            $chapter_id = post('chapter_id');
            if ($chapter = $this->audiobook->chapters()->find($chapter_id)) {
                $chapter->service()->delete();
            }

            $this->fresh();

            return $this->renderChapters();
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());

            return $this->renderChapters();
        }
    }

    public function onUpdate(): array
    {
        try {
            $this->service->update(post());
            $this->vals();
            $this->setAllowedStatuses();

            return [
                PartialSpawns::SPAWN_EDIT_AUDIOBOOK_CHAPTERS->value => $this->renderPartial('@chapters'),
                PartialSpawns::SPAWN_EDIT_AUDIOBOOK_SETTINGS->value => $this->renderPartial('@settings'),
            ];
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            return [];
        }
    }

    public function onUpdateSortOrder(): array
    {
        $partial = fn() => [
            '#audiobooker-chapters' => $this->renderPartial('@chapters', ['audiobook' => $this->audiobook]),
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
}
