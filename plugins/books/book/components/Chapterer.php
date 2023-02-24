<?php

namespace Books\Book\Components;

use Books\Book\Classes\ChapterService;
use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Redirect;
use Request;
use ValidationException;
use Validator;

/**
 * Chapterer Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Chapterer extends ComponentBase
{
    protected User $user;

    protected Book $book;

    protected Edition $ebook;

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
        $this->ebook = $this->book->ebook;
        $this->chapter = $this->ebook->chapters()->find($this->param('chapter_id')) ?? new Chapter();
        $this->chapterManager = new ChapterService($this->chapter);
        $this->prepareVals();
    }

    public function prepareVals()
    {
        $this->page['ebook'] = $this->ebook;
        $this->page['chapter'] = $this->chapter;
    }

    public function onSave()
    {
        try {
            $data = post();
            if ($data['chapter_content'] ?? false) {
                $data['content'] = $data['chapter_content'];
            }

            if ($status = $data['action'] ?? false) {
                switch ($status) {
                    case 'published_at':

                        $data['status'] = ChapterStatus::PUBLISHED;
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
                        break;

                    case 'save_as_draft':

                        $data['status'] = ChapterStatus::DRAFT;
                        $data['published_at'] = null;
                        break;

                    case 'publish_now':

                        $data['status'] = ChapterStatus::PUBLISHED;
                        $data['published_at'] = null;
                        break;
                }
            }
            $validator = Validator::make(
                $data,
                collect((new Chapter())->rules)->only([
                    'title', 'content', 'published_at',
                ])->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $this->chapter = $this->chapterManager->setEdition($this->ebook)->from($data);

            return Redirect::to('/about-book/' . $this->book->id)->withFragment('#tab-electronic');
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }
}
