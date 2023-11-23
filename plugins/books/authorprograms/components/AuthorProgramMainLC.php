<?php namespace Books\AuthorPrograms\Components;

use Books\AuthorPrograms\Classes\Enums\ProgramsEnums;
use Books\AuthorPrograms\Models\AuthorsPrograms;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use October\Rain\Exception\ValidationException;
use RainLab\User\Facades\Auth;
use Validator;

/**
 * AuthorProgramMainLC Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class AuthorProgramMainLC extends ComponentBase
{
    private $user;

    public function componentDetails()
    {
        return [
            'name' => 'AuthorProgramMainLC Component',
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

    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }
        $user = Auth::getUser();
        $this->user = $user;

        $this->prepareVals();
    }

    protected function prepareVals()
    {
        $this->page['author_program'] = [
            'reader_birthday' => $this->user->programs()->userProgramReaderBirthday()->first(),
            'new_reader' => $this->user->programs()->userProgramNewReader()->first(),
            'regular_reader' => $this->user->programs()->userProgramRegularReader()->first(),
        ];
    }

    public function onSaveProgram(): RedirectResponse|array
    {
        try {
            $program = AuthorsPrograms::find(post('id'));
            if(!$program) {
                $program = new AuthorsPrograms();
            }
            $program->user_id = $this->user->id;
            if (post('program_type') === ProgramsEnums::READER_BIRTHDAY->value) {
                $program->program = ProgramsEnums::READER_BIRTHDAY->value;
                $program->condition = ['percent' => post('percent')];
            }
            if (post('program_type') === ProgramsEnums::NEW_READER->value) {
                $program->program = ProgramsEnums::NEW_READER->value;
                $program->condition = ['percent' => (int)post('percent'), 'days' => (int)post('days')];
            }
            if (post('program_type') === ProgramsEnums::REGULAR_READER->value) {
                $program->program = ProgramsEnums::REGULAR_READER->value;
                $program->condition = ['percent' => (int)post('percent'), 'books' => (int)post('books')];
            }
            $program->save();
            Flash::info('Настройки успешно сохранены');
            return Redirect::to('/lc-commercial/');
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return [];
        }
    }

    public function onDeleteProgram(): RedirectResponse|array
    {
        try {
            AuthorsPrograms::destroy(post('id'));
            Flash::warning('Настройки успешно удалены');
            return Redirect::to('/lc-commercial/');
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return [];
        }
    }

    public function onUpdateProgram()
    {
        return [
            '#'.post('tag').'_form' => $this->renderPartial('@program_form', [
                'program' => $this->page['author_program'][post('tag')],
                'form_type' => post('tag'),
                'program_type' => post('tag'),
            ])
        ];
    }
}
