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

        if (!Cookie::get('newer_show_reader_birthday_program')) {
            if ($reader?->birthday->isBirthday() && Cookie::get('show_reader_birthday_program')) {
                $authors = Profile::leftJoin('books_authors_programs as ap', 'books_profile_profiles.user_id', 'ap.user_id')
                    ->where('books_profile_profiles.user_id', '!=', $reader->getAuthIdentifier())
                    ->where('ap.program', ProgramsEnums::READER_BIRTHDAY->value)
                    ->get();
                $programs = AuthorsPrograms::where('user_id', '!=', $reader->getAuthIdentifier())
                    ->where('program', ProgramsEnums::READER_BIRTHDAY->value)
                    ->get()
                    ->keyBy('user_id')
                    ->toArray();
                $data['is_open'] = true;
                $data['#reader-birthday-modal'] = $this->renderPartial('@reader_birthday', ['authors' => $authors, 'programs' => $programs]);
            }
        }
        return $data;
    }

    public function onLoadNewReaderModal()
    {
        $data['is_open'] = false;
        $reader = Auth::user();
            dd(AuthorsPrograms::where('program', ProgramsEnums::REGULAR_READER)->get()->pluck('id')->toArray());
        if (!Cookie::get('newer_show_reader_birthday_program')) {
            if ($reader?->birthday->isBirthday() && Cookie::get('show_reader_birthday_program')) {
                $authorsWithProgram = AuthorsPrograms::where('program', ProgramsEnums::REGULAR_READER)->get('id');

                $data['is_open'] = true;
                $data['#reader-birthday-modal'] = $this->renderPartial('@reader_birthday', ['authors' => [], 'programs' => []]);
            }
        }
        return $data;
    }

    public function onLoadRegularReaderModal()
    {
        $data['is_open'] = false;
        $reader = Auth::user();

        if (!Cookie::get('newer_show_new_reader_program')) {

            if ($reader?->birthday->isBirthday() && Cookie::get('show_new_reader_program')) {

                $data['is_open'] = true;
                $data['#reader-birthday-modal'] = $this->renderPartial('@reader_birthday', ['authors' => [], 'programs' => []]);
            }
        }
        return $data;
    }

    public function onCloseReaderBirthdayModal()
    {
        Cookie::queue('show_reader_birthday_program', false);
    }

    public function onCloseNewReaderModal()
    {
        Cookie::queue('show_new_reader_program', false);
    }

    public function onCloseRegularReaderModal()
    {
        Cookie::queue('show_regular_reader_program', false);
    }

    public function onNeverShowReaderBirthdayModal()
    {
        Cookie::queue('newer_show_reader_birthday_program', true);
    }

    public function onNeverShowNewReaderModal()
    {
        Cookie::queue('newer_show_new_reader_program', true);
    }

    public function onNeverShowRegularReaderModal()
    {
        Cookie::queue('newer_show_regular_reader_program', true);
    }
}
