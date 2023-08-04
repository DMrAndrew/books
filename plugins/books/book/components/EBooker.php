<?php

namespace Books\Book\Components;

use App\classes\PartialSpawns;
use ApplicationException;
use Books\Book\Classes\EditionService;
use Books\Book\Classes\Enums\ContentTypeEnum;
use Books\Book\Models\Edition;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Log;
use RainLab\User\Facades\Auth;
use Redirect;
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
        $this->setAllowedStatuses();
        $this->service = new EditionService($this->ebook);
    }

    public function fresh()
    {
        $this->ebook = Auth::getUser()?->profile->books()->with(['ebook.chapters' => fn($chapters) => $chapters->withDeferredState()])->find($this->property('book_id'))?->ebook;
        if (!$this->ebook) {
            throw new ApplicationException('Электронное издание книги не найден.');
        }
    }

    protected function setAllowedStatuses()
    {
        $this->page['bookStatusCases'] = $this->ebook->getAllowedStatusCases();
    }

    public function vals()
    {
        $this->fresh();
        $this->page['ebook'] = $this->ebook;
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

    public function onUpdateSortOrder(): array
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

    protected function renderChapters()
    {

        return [
            '#ebooker-chapters' => $this->renderPartial('@chapters', ['ebook' => $this->ebook]),
        ];
    }

    public function onDeleteChapter(): array
    {

        try {
            $chapter_id = post('chapter_id');
            if ($chapter = $this->ebook->chapters()->find($chapter_id)) {
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
        if (!$this->ebook->book->genres()->count()) {
            Flash::error('Добавьте книге хотя бы один жанр.');
            return [];
        }
        try {
            $this->service->update(post());
            $this->vals();
            $this->setAllowedStatuses();

            return [
                '#about-header' => $this->renderPartial('book/about-header'),
                PartialSpawns::SPAWN_EDIT_EBOOK_CHAPTERS->value => $this->renderPartial('@chapters'),
                PartialSpawns::SPAWN_EDIT_EBOOK_SETTINGS->value => $this->renderPartial('@settings'),
            ];
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            return [];
        }
    }

    protected function chaptersQuery()
    {
        return $this->ebook
            ->chapters()
            ->whereHas('deferredContentOpened', fn($q) => $q->deferredOpened()->notRequested());
    }

    public function onRequestDeferredModal(): array
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

    public function onRequestDeferred(): array|RedirectResponse
    {

        if (!count($this->postChaptersIds())) {
            Flash::info('Выберите хотя бы 1 главу.');
            return [];
        }

        $this->chaptersQuery()
            ->find($this->postChaptersIds())
            ->map
            ->deferredContentOpened
            ->map
            ->service()
            ->each
            ->markRequested(post('comment'));
        return Redirect::refresh();
    }

    public function onCancelRequestDeferred(): array|RedirectResponse
    {
        return $this->onCancel(ContentTypeEnum::DEFERRED_UPDATE);
    }

    public function onCancelDeleting(): array|RedirectResponse
    {
        return $this->onCancel(ContentTypeEnum::DEFERRED_DELETE);
    }

    public function onCancel(ContentTypeEnum $typeEnum): array
    {
        try {
            $relation = match ($typeEnum) {
                ContentTypeEnum::DEFERRED_UPDATE => 'deferredContentOpened',
                ContentTypeEnum::DEFERRED_DELETE => 'deletedContent',
            };
            $this->ebook->chapters()->find($this->postChapterId())?->{$relation}?->service()->markCanceled();
            $this->fresh();
            return $this->renderChapters();
        } catch (Exception $exception) {
            Flash::error($exception instanceof ValidationException ? $exception->getMessage() : 'Не удалось выполнить запрос');
            Log::error($exception->getMessage());
            return [];
        }
    }

    public function postChaptersIds(): array
    {
        return collect(post('chapters'))->filter(fn($i) => in_array($i, ['1', 'on']))->keys()->toArray();
    }

    public function postChapterId()
    {
        return post('chapter_id');
    }
}
