<?php namespace Books\AuthorPrograms\Components;

use Books\AuthorPrograms\Classes\Enums\ProgramsEnums;
use Books\AuthorPrograms\Models\AuthorsPrograms;
use Books\Book\Models\UserBook;
use Books\Profile\Models\Profile;
use Books\User\Models\User;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RainLab\User\Components\Session;
use RainLab\User\Facades\Auth;

/**
 * ModalNotification Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ModalNotification extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'ModalNotification Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->addJs('/plugins/books/authorprograms/asset/js/script.js?v=' . Carbon::now()->timestamp);
    }

    public function onLoadReaderBirthdayModal()
    {
        $data['is_open'] = false;
        $reader = Auth::user();
        $modalId = '#reader-birthday-modal';

        if (!Cookie::has('newer_show_reader_birthday_program')) {
            if ($reader?->birthday->isBirthday() && Cookie::get('show_reader_birthday_program')) {
                $authors = Profile::query()
                    ->with(['avatar'])
                    ->leftJoin('books_authors_programs', 'books_profile_profiles.user_id', '=', 'books_authors_programs.user_id')
                    ->leftJoin('books_book_authors', 'books_profile_profiles.id', '=', 'books_book_authors.profile_id')
                    ->leftJoin('books_book_genre', 'books_book_genre.book_id', '=', 'books_book_authors.book_id')
                    ->where('books_profile_profiles.user_id', '!=', $reader->getAuthIdentifier())
                    ->whereNotIn('books_book_genre.genre_id', $reader->unloved_genres)
                    ->where('books_authors_programs.program', ProgramsEnums::READER_BIRTHDAY->value)
                    ->groupBy('books_profile_profiles.id')
                    ->select('books_profile_profiles.id', 'books_profile_profiles.username', 'books_authors_programs.program', 'books_authors_programs.condition')
                    ->withCasts(['condition' => 'object'])
                    ->get();

                if ($authors->count() > 1) {
                    $renderData = [
                        'authors' => $authors,
                        'closeCookie' => 'show_reader_birthday_program',
                        'modail_id' => '#reader-birthday-modal',
                        'title' => 'Скидка на книги авторов в честь Вашего дня рождения',
                        'sudtitle' => 'Вы участвуете в программе следующих авторов:',
                        'neverShowCookie' => 'newer_show_reader_birthday_program',
                    ];
                    $template = '@multiple_authors';
                } else {
                    $author = $authors->first();
                    $renderData = [
                        'avatar' => $author->avatar->path,
                        'author' => $author,
                        'title' => 'Скидка на книги автора <a href="/author-page/' . $author->id . '">' . $author->username . ' </a> в честь Вашего дня рождения',
                        'subtitle' => 'Для вас будет действовать персональная скидка ' . $author->condition->percent . '% на все его книги',
                        'closeCookie' => 'show_reader_birthday_program',
                        'modail_id' => $modalId,
                        'neverShowCookie' => 'newer_show_reader_birthday_program',
                    ];
                    $template = '@single_author';
                }

                $data['is_open'] = true;
                $data[$modalId] = $this->renderPartial($template, $renderData);
            }
        }
        return $data;
    }

    public function onLoadNewReaderModal()
    {
        $data['is_open'] = false;
        $reader = Auth::user();
        $modalId = '#new-reader-modal';

        if ($reader) {
            $allProgramsAuthors = AuthorsPrograms::query()
                ->leftJoin('books_profile_profiles', 'books_profile_profiles.user_id', '=', 'books_authors_programs.user_id')
                ->where('program', ProgramsEnums::NEW_READER->value)
                ->select(
                    'books_profile_profiles.id as profile_id',
                    'books_profile_profiles.user_id',
                    'books_authors_programs.program',
                    'books_authors_programs.condition',
                )
                ->get();

            $profilesIds = collect();

            foreach ($allProgramsAuthors as $programsAuthor) {

                $authorOfPurchasedBooks = UserBook::leftJoin('books_book_editions', 'books_user_books.ownable_id', '=', 'books_book_editions.id')
                    ->leftJoin('books_book_authors', 'books_book_editions.book_id', '=', 'books_book_authors.book_id')
                    ->select(DB::raw('count(*) as counter'), 'books_book_authors.profile_id')
                    ->where('books_user_books.user_id', $reader->getAuthIdentifier())
                    ->where('books_book_authors.profile_id', $programsAuthor->profile_id)
                    ->whereBetween('books_user_books.created_at', [Carbon::now()->subDays($programsAuthor->condition->days), Carbon::now()])
                    ->having('counter', '=', 1)
                    ->groupBy('books_book_authors.profile_id')
                    ->get();

                if(!$authorOfPurchasedBooks->isEmpty()) {
                    $profilesIds[] = $authorOfPurchasedBooks->first()->profile_id;
                }
            }


            $authors = Profile::query()
                ->with(['avatar'])
                ->leftJoin('books_authors_programs', 'books_authors_programs.user_id', '=', 'books_profile_profiles.user_id')
                ->where('books_authors_programs.program', '=', ProgramsEnums::NEW_READER->value)
                ->whereIn('books_profile_profiles.id', $profilesIds)
                ->select('books_profile_profiles.id', 'books_profile_profiles.username', 'books_authors_programs.program', 'books_authors_programs.condition')
                ->withCasts(['condition' => 'object'])
                ->get();

            if (!Cookie::has('newer_show_new_reader_program')) {
                if ($authors->count() && Cookie::get('show_new_reader_program')) {
                    if ($authors->count() > 1) {
                        $renderData = [
                            'authors' => $authors,
                            'closeCookie' => 'show_new_reader_program',
                            'modail_id' => $modalId,
                            'title' => 'Вы участвуете в программе "Новый читатель"',
                            'sudtitle' => 'Для вас будут действовать персональные скидки у следующих авторов:',
                            'neverShowCookie' => 'newer_show_new_reader_program',
                        ];
                        $template = '@multiple_authors';
                    } else {
                        $author = $authors->first();
                        $renderData = [
                            'avatar' => $author->avatar->path,
                            'author' => $author,
                            'title' => 'Вы участвуете в программе "Новый читатель" автора <a href="/author-page/' . $author->id . '">' . $author->username . ' </a>',
                            'subtitle' => 'Для вас будет действовать персональная скидка ' . $author->condition->percent . '% в течении ' . $author->condition->days . ' дней',
                            'closeCookie' => 'show_new_reader_program',
                            'modail_id' => $modalId,
                            'neverShowCookie' => 'newer_show_reader_birthday_program',
                        ];
                        $template = '@single_author';
                    }

                    $data['is_open'] = true;
                    $data[$modalId] = $this->renderPartial($template, $renderData);
                }
            }
        }

        return $data;
    }

    public function onLoadRegularReaderModal()
    {
        $data['is_open'] = false;
        $reader = Auth::user();
        $modalId = '#regular-reader-modal';

        if ($reader) {
            $allProgramsAuthors = AuthorsPrograms::query()
                ->leftJoin('books_profile_profiles', 'books_profile_profiles.user_id', '=', 'books_authors_programs.user_id')
                ->where('program', ProgramsEnums::REGULAR_READER->value)
                ->select(
                    'books_profile_profiles.id as profile_id',
                    'books_profile_profiles.user_id',
                    'books_authors_programs.program',
                    'books_authors_programs.condition',
                )
                ->get();

            $profilesIds = collect();

            foreach ($allProgramsAuthors as $programsAuthor) {

                $authorOfPurchasedBooks = UserBook::leftJoin('books_book_editions', 'books_user_books.ownable_id', '=', 'books_book_editions.id')
                    ->leftJoin('books_book_authors', 'books_book_editions.book_id', '=', 'books_book_authors.book_id')
                    ->select(DB::raw('count(*) as counter'), 'books_book_authors.profile_id')
                    ->where('books_user_books.user_id', $reader->getAuthIdentifier())
                    ->where('books_book_authors.profile_id', $programsAuthor->profile_id)
                    ->having('counter', '>', $programsAuthor->condition->books)
                    ->groupBy('books_book_authors.profile_id')
                    ->get();

                if(!$authorOfPurchasedBooks->isEmpty()) {
                    $profilesIds[] = $authorOfPurchasedBooks->first()->profile_id;
                }
            }


            $authors = Profile::query()
                ->with(['avatar'])
                ->leftJoin('books_authors_programs', 'books_authors_programs.user_id', '=', 'books_profile_profiles.user_id')
                ->where('books_authors_programs.program', '=', ProgramsEnums::REGULAR_READER->value)
                ->whereIn('books_profile_profiles.id', $profilesIds)
                ->select('books_profile_profiles.id', 'books_profile_profiles.username', 'books_authors_programs.program', 'books_authors_programs.condition')
                ->withCasts(['condition' => 'object'])
                ->get();

            if (!Cookie::has('newer_show_regular_reader_program')) {
                if ($authors->count() && Cookie::get('show_regular_reader_program')) {
                    if ($authors->count() > 1) {
                        $renderData = [
                            'authors' => $authors,
                            'closeCookie' => 'show_regular_reader_program',
                            'modail_id' => $modalId,
                            'title' => 'Вы участвуете в программе "Мой постоянный читатель"',
                            'sudtitle' => 'Для вас будут действовать персональные скидки у следующих авторов:',
                            'neverShowCookie' => 'newer_show_regular_birthday_program',
                        ];
                        $template = '@multiple_authors';
                    } else {
                        $author = $authors->first();
                        $renderData = [
                            'avatar' => $author->avatar->path,
                            'author' => $author,
                            'title' => 'Вы участвуете в программе "Мой постоянный читатель" автора <a href="/author-page/' . $author->id . '">' . $author->username . ' </a>',
                            'subtitle' => 'Для вас будет действовать персональная скидка ' . $author->condition->percent . '% на все книги автора.',
                            'closeCookie' => 'show_regular_reader_program',
                            'modail_id' => $modalId,
                            'neverShowCookie' => 'newer_show_regular_birthday_program',
                        ];
                        $template = '@single_author';
                    }

                    $data['is_open'] = true;
                    $data[$modalId] = $this->renderPartial($template, $renderData);
                }
            }
        }

        return $data;
    }

    public function onCloseModal()
    {
        Cookie::queue(post('cookieName'), false);
    }

    public function onNeverShowModal()
    {
        Cookie::queue(post('cookieName'), true);
    }
}
