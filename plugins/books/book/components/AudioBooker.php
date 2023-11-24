<?php namespace Books\Book\Components;

use App\classes\PartialSpawns;
use ApplicationException;
use Books\Book\Classes\EditionService;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Services\AudioFileListenTokenService;
use Books\Book\Models\Chapter;
use Books\Book\Models\Content;
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

        AudioFileListenTokenService::generateListenTokenForUser();
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
//        $this->page['request_premoderation'] = $this->audiobook?->chapters->some(
//            function (Chapter $chapter) {
//                return $chapter->deferred?->some(
//                    function (Content $d) {
//                        return $d->infoHelper()->requestAllowed();
//                    }
//                );
//            }
//        );
        /**
         * Запросить премодерацию
         * Кнопка активна, когда необходимо Отложенное редактирование
         * и есть глава в статусе Pending
         */
        $this->page['request_premoderation'] = true;//$this->audiobook?->chapters->some(fn(Chapter $chapter) => $chapter->deferred?->some(fn(Content $d) => $d->infoHelper()->requestAllowed()));
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

    public function onRequestPremoderationModal(): array
    {
        $closure = fn($builder) => $this->postChapterId() ? $builder->find([$this->postChapterId()]) : $builder->get();
        return array_merge(
            [
                PartialSpawns::SPAWN_MODAL->value => $this->renderPartial('modals/deferred_chapter_request', [
                    'chapters' => $closure($this->chaptersQuery())
                ]),
            ],
            $this->renderChapters()
        );
    }
}
