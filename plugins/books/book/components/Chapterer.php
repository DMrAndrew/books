<?php namespace Books\Book\Components;


use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\EbookEdition;
use Exception;
use Flash;
use RainLab\User\Models\User;
use Redirect;
use Request;
use Validator;
use Carbon\Carbon;
use ValidationException;
use Cms\Classes\ComponentBase;
use RainLab\User\Facades\Auth;
use Books\Book\Models\ChapterStatus;
use Books\Book\Classes\ChapterManager;

/**
 * Chapterer Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Chapterer extends ComponentBase
{
    protected User $user;
    protected Book $book;
    protected EbookEdition $ebook;
    protected ?Chapter $chapter;
    protected ChapterManager $chapterManager;

    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'Chapterer Component',
            'description' => 'No description provided yet...'
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
        $this->chapter = $this->ebook->chapters()->find($this->param('chapter_id')) ?? null;
        $this->chapterManager = new ChapterManager($this->ebook);
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
            $validator = Validator::make(
                $data,
                collect((new Chapter())->rules)->only([
                    'title', 'content', 'published_at'
                ])->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }


            if ($status = $data['action'] ?? false) {
                switch ($status) {
                    case 'published_at':
                    {
                        $data['status'] = ChapterStatus::PUBLISHED;
                        if (!isset($data['published_at'])) {
                            new ValidationException(['published_at' => 'Укажите дату публикации.']);
                        }
                        if (!Carbon::canBeCreatedFromFormat($data['published_at'] ?? '', 'Y-m-d\TH:i')) {
                            throw new ValidationException(['published_at' => 'Не удалось получить дату публикации. Укажите дату в формате Y-m-d H:i']);
                        }
                        $data['published_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $data['published_at']);
                        break;
                    }
                    case 'save_as_draft':
                    {
                        $data['status'] = ChapterStatus::DRAFT;
                        $data['published_at'] = null;
                        break;
                    }
                    case 'publish_now':
                    {
                        $data['status'] = ChapterStatus::PUBLISHED;
                        $data['published_at'] = null;
                        break;
                    }
                }
            }

            $this->chapter = $this->chapter ? $this->chapterManager->update($this->chapter, $data) : $this->chapterManager->create($data);

            return Redirect::to("/about-book/" . $this->book->id)->withFragment('#tab-electronic');
        } catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }

    }
}
