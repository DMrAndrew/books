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
        $this->service = new EditionService($this->ebook);
    }

    public function fresh()
    {
        $this->ebook = Auth::getUser()?->profile->books()->with(['ebook.chapters' => fn($chapters) => $chapters->withDeferredState()])->find($this->property('book_id'))?->ebook;
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
                PartialSpawns::SPAWN_EDIT_EBOOK_CHAPTERS->value => $this->renderPartial('@chapters'),
                PartialSpawns::SPAWN_EDIT_EBOOK_SETTINGS->value => $this->renderPartial('@settings'),
            ];
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            $this->vals();

            return [
                '#ebook-settings' => $this->renderPartial('@settings'),
            ];
        }
    }

    public function onRequestDeferredModal()
    {
        return [
            PartialSpawns::SPAWN_MODAL->value => $this->renderPartial('modals/deferred_chapters_request', ['chapter' => $this->ebook->chapters()->find($this->postChapterId())]),
        ];
    }

    public function onRequestDeferred()
    {
        $this->ebook
            ->chapters()->whereHas('deferredContentOpened', fn($q) => $q->deferredOpened()->notRequested())
            ->where('id', $this->postChapterId())
            ->first()
            ->deferredContentOpened?->service()->markRequested(post('comment'));
        return Redirect::refresh();
        $this->vals();
        return [
            PartialSpawns::SPAWN_MODAL->value => '',
            PartialSpawns::SPAWN_EDIT_EBOOK_CHAPTERS->value => $this->renderPartial('@chapters'),
        ];
    }

    public function onCancelRequestDeferred(): array|RedirectResponse
    {
        return $this->onCancel(ContentTypeEnum::DEFERRED_UPDATE);
    }

    public function onCancelDeleting(): array|RedirectResponse
    {
        return $this->onCancel(ContentTypeEnum::DEFERRED_DELETE);
    }

    public function onCancel(ContentTypeEnum $typeEnum): array|RedirectResponse
    {
        try {
            $relation = match ($typeEnum) {
                ContentTypeEnum::DEFERRED_UPDATE => 'deferredContentOpened',
                ContentTypeEnum::DEFERRED_DELETE => 'deletedContent',
            };
            $this->ebook->chapters()->find($this->postChapterId())?->{$relation}?->service()->markCanceled();
            return Redirect::refresh();
        } catch (Exception $exception) {
            Flash::error($exception instanceof ValidationException ? $exception->getMessage() : 'Не удалось выполнить запрос');
            Log::error($exception->getMessage());
            return [];
        }
    }

    public function postChapterId()
    {
        return post('chapter_id');
    }
}
