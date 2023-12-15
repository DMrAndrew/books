<?php

namespace Books\Book\Components;

use Books\Book\Classes\ChapterService;
use Books\Book\Classes\DeferredAudioChapterService;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Services\AudioFileListenTokenService;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\FileUploader\Components\AudioUploader;
use Books\FileUploader\Components\FileUploader;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use ValidationException;
use Validator;

/**
 * Chapterer Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AudioChapterer extends ComponentBase
{
    protected User $user;

    protected Book $book;

    protected Edition $audiobook;

    protected Chapter $chapter;

    protected ChapterService $chapterManager;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Chapterer Component',
            'description' => 'No description provided yet...',
        ];
    }

    /**
     * defineProperties for the component
     *
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }

        $this->user = Auth::getUser();
        $this->book = $this->user->profile->books()->find($this->param('book_id')) ?? abort(404);
        $this->audiobook = $this->getAudioBook();
        $this->chapter = $this->audiobook->chapters()
                ->withDrafts()
                ->find($this->param('chapter_id'))
            ?? new Chapter([
                'edition_id' => $this->audiobook->id,
                'type' => EditionsEnums::Audio,
            ]);
        //$this->chapterManager = ($this->audiobook->shouldDeferredUpdate() ? $this->chapter->deferredService() : $this->chapter->service())->setEdition($this->audiobook);
        $this->prepareVals();

        $component = $this->addComponent(
            AudioUploader::class,
            'audioUploader',
            [
                'modelClass' => Chapter::class,
                'deferredBinding' => true, // всегда отложенное, чтобы не заменялся/удалялся файл без сохранения формы
                "maxSize" => 30,
                "fileTypes" => ".mp3,.aac",
            ]
        );
        $component->bindModel('audio', $this->chapter);

        AudioFileListenTokenService::generateListenTokenForUser();

        $this->registerBreadcrumbs();
    }

    public function prepareVals()
    {
        $this->page['book'] = $this->book;
        $this->page['audiobook'] = $this->audiobook;
        $this->page['chapter'] = $this->chapter;
        $this->page['times'] = collect(CarbonPeriod::create(today(), '1 hour', today()->copy()->addHours(23))->toArray())->map->format('H:i');
    }

    public function onSave()
    {
        try {
            $data = collect(post());

            $data['type'] = EditionsEnums::Audio;
            $data['edition_id'] = $this->audiobook?->id;

            if ($status = $data['action'] ?? false) {
                switch ($status) {
                    case 'published_at':

                        $data['status'] = ChapterStatus::PLANNED;
                        if (!isset($data['published_at_date'])) {
                            new ValidationException(['published_at' => 'Укажите дату публикации.']);
                        }
                        if (!isset($data['published_at_time'])) {
                            new ValidationException(['published_at' => 'Укажите время публикации.']);
                        }
                        if (!Carbon::canBeCreatedFromFormat($data['published_at_date'] ?? '', 'd.m.Y')) {
                            throw new ValidationException(['published_at' => 'Не удалось получить дату публикации. Укажите дату в формате d.m.Y']);
                        }
                        $data['published_at'] = Carbon::createFromFormat('d.m.Y', $data['published_at_date'])->setTimeFromTimeString($data['published_at_time']);
                        if (Carbon::now()->gte($data->get('published_at'))) {
                            throw new ValidationException(['published_at' => 'Дата и время публикации должны быть больше текущего времени.']);
                        }
                        break;

                    case 'save_as_draft':

                        $data['status'] = ChapterStatus::DRAFT;
                        break;

                    case 'publish_now':

                        $data['status'] = ChapterStatus::PUBLISHED;
                        break;
                }
            }
            $validator = Validator::make(
                $data->toArray(),
                collect((new Chapter())->rules)->only([
                    'title', 'audio', 'published_at', 'type'
                ])->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            /**
             * Если редактирование только через премодерацию
             */
            if ($this->audiobook->shouldDeferredUpdate()) {

                if ($this->chapter->exists) {

                    $this->chapter
                        ->fill($data->toArray())
                        ->saveAsDraft($data->toArray(), sessionKey: $this->getSessionKey());
                } else {

                    // working but not current
                    $this->chapter
                        ->fill($data->toArray())
                        ->save(sessionKey: $this->getSessionKey());

                    $this->chapter->setCurrent();
                    $this->chapter->saveQuietly();

                    $this->chapter->fresh();
                }
            }

            /**
             * Публикация без премодерации
             */
            else {
                $this->chapter
                    ->fill($data->toArray())
                    ->save(sessionKey: $this->getSessionKey());

                    $this->chapter->setLive();
                    $this->chapter->saveQuietly();

                $this->chapter->edition->chapters->each->setNeighbours();
            }
            
            return Redirect::to('/about-book/' . $this->book->id)->withFragment('#audiobook')->setLastModified(now());

        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            return [];
        }
    }

    private function getAudioBook(): Edition
    {
        return $this->book->audiobook
            ?? $this->book->audiobook()->create([
                'type' => EditionsEnums::Audio,
                'status' => BookStatus::HIDDEN
            ]);
    }

    public function getSessionKey()
    {
        return post('_session_key');
    }

    public function onRefreshFiles()
    {
        $this->pageCycle();
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-add-book-add-audio', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Книги', '/lc-books');
            $trail->push($this->book->title, '/about-book/' . $this->book->id);
            $trail->push('Добавление аудиокниги');
        });
    }
}
