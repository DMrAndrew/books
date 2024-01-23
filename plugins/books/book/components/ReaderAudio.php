<?php

namespace Books\Book\Components;

use Books\Book\Classes\ReaderAudio as Service;
use Books\Book\Classes\Services\AudioFileListenTokenService;
use Books\Book\Classes\Traits\InjectBookStuff;
use Books\Book\Models\AudioReadProgress;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use Validator;

/**
 * Reader Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ReaderAudio extends ComponentBase
{
    use InjectBookStuff;

    protected ?Book $book;

    protected ?Chapter $chapter;

    protected ?User $user;

    protected ?Service $service = null;

    protected int $audiobook_id;

    protected int $chapter_id;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'ReaderAudio Component',
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
        $this->user = Auth::getUser();
        $this->audiobook_id = (int) $this->param('book_id')
            ?? $this->controller->run('404');
        $this->book = Book::findForPublic($this->audiobook_id, $this->user);

        if (!$this->book?->audiobook) {
            $this->controller->run('404');
        }

        $this->chapter_id = (int) $this->param('chapter_id');
        $this->chapter = $this->chapter_id ? Chapter::public()->find($this->chapter_id)
            ?? $this->controller->run('404')
            : null;
        $this->tryInjectAdultModal();
        $this->addMeta();
        //$recommend = $this->addComponent(Widget::class, 'recommend');
        //$recommend->setUpWidget(WidgetEnum::recommend, short: true);
        //$advert = $this->addComponent(AdvertBanner::class, 'advertBanner');

        AudioFileListenTokenService::generateListenTokenForUser();

        $this->registerBreadcrumbs();
    }

    public function onRun()
    {
        if (! $this->chapter) {
            // proceed from last progress
            if ($lastReadedChapter = $this->getLastReadedChapter()) {
                return Redirect::to(sprintf('/readeraudio/%s/%s', $this->book->id, $lastReadedChapter->id));
            }

            // otherwise first chapter
            if ($firstChapter = $this->book->audiobook
                ?->chapters()
                ->published()
                ->moderationPublished()
                ->whereHas('audio')
                ->first()) {
                return Redirect::to(sprintf('/readeraudio/%s/%s', $this->book->id, $firstChapter->id));
            }
        }

        if (! $this->service()->isPageAllowed()) {
            return Redirect::to(sprintf('/out-of-free/%s/%s', $this->book->audiobook->id, $this->chapter?->id));
        }
    }

    public function onRender()
    {
        $this->prepareVals();
    }

    public function getLastTrackedPaginator()
    {
        return $this->book
            ->paginationTrackers()
            ->latestActiveTracker()
            ->first()?->trackable;
    }

    public function service(): Service
    {
        if (! $this->service) {
            $this->service = new Service(
                book: $this->book,
                chapter: $this->chapter,
                user: $this->user
            );
        }

        return $this->service;
    }

    public function prepareVals()
    {
        foreach ($this->service()->getReaderPage() as $key => $item) {
            $this->page[$key] = $item;
        }

        $this->page['user'] = $this->user;
        $this->page['audioProgress'] = $this->getAudioReadProgress();
    }

    public function onNext()
    {
        if ($chapter = $this->service()->nextChapter()) {
            return Redirect::to(sprintf('/readeraudio/%s/%s', $this->book->id, $chapter->id));
        }
        if ($this->service()->readBtn()) {
            $this->user->library($this->book)->read();

            return Redirect::to(sprintf('/book-card/%s', $this->book->id));
        }

        return false;
    }

    public function onPrev()
    {
        if ($chapter = $this->service()->prevChapter()) {
            return Redirect::to(sprintf('/readeraudio/%s/%s', $this->book->id, $chapter->id));
        }

        return false;
    }

    public function onChapter()
    {
        if ($chapter = post('value')) {
            return Redirect::to(sprintf('/readeraudio/%s/%s', $this->book->id, $chapter));
        }
    }

    public function onMove()
    {
        $this->prepareVals();

        return [
            '#reader-body-spawn' => $this->renderPartial('@body'),
            '.reader-user-section' => $this->renderPartial('@user-section'),
        ];
    }

    /**
     * @return array
     */
    public function onSaveProgress(): array
    {
        /**
         * Only for authenticated users
         */
        $user = Auth::getUser();
        if (!$user) {
            return [];
        }

        /**
         * Validation
         */
        $data = [
            'user_id' => $user->id,
            'book_id' => post('book'),
            'chapter_id' => post('chapter'),
            'progress' => post('progress'),
        ];
        $validation = Validator::make(
            $data,
            (new AudioReadProgress())->rules
        );
        if ($validation->fails()) {
            return [];
        }

        /**
         * Update progress
         */
        AudioReadProgress::updateOrCreate([
            'user_id' => $data['user_id'],
            'book_id' => $data['book_id'],
            'chapter_id' => $data['chapter_id'],
        ],[
            'progress' => $data['progress'],
        ]);

        return [];
    }

    /**
     * @return Chapter|null
     */
    private function getLastReadedChapter(): ?Chapter
    {
        if (!$this->user) {
            return null;
        }

        $readedChapters = AudioReadProgress::query()
            ->book($this->book)
            ->user($this->user)
            ->get();

        if (!$readedChapters) {
            return null;
        }

        return $this->book->audiobook
            ?->chapters()
            ->published()
            ->moderationPublished()
            ->whereIn('id', $readedChapters->pluck('chapter_id')->toArray())
            ->orderByDesc('sort_order')
            ->get()
            ->first();
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);
        $manager->register('readeraudio', function (BreadcrumbsGenerator $trail, $params) {

            /** Главная */
            $trail->parent('home');

            /** Книги */
            $trail->push('Книги', url('/listing'));

            /** Жанр */
            $genre = $this->book->genres->first();
            if ($genre) {
                $trail->push($genre->name, url('/listing?genre=' . $genre->id));
            }

            /** Название книги */
            $trail->push($this->book->title, url('/book-card/' . $this->book->id));
        });
    }

    /**
     * @return int|null
     */
    private function getAudioReadProgress(): ?int
    {
        if (!$this->user || !$this->book || !$this->chapter) {
            return null;
        }

        $audioReadProgress = AudioReadProgress
            ::user($this->user)
            ->book($this->book)
            ->chapter($this->chapter)
            ->first();

        return $audioReadProgress?->progress;
    }
}
