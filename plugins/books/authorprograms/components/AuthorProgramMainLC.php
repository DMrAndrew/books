<?php namespace Books\AuthorPrograms\Components;

use Books\AuthorPrograms\Classes\Enums\ProgramsEnums;
use Books\AuthorPrograms\Models\AuthorsPrograms;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Illuminate\Http\RedirectResponse;
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

    public function onConnectProgramReaderBirthday(): RedirectResponse|array
    {
        try {
            $data['user'] = $this->user->id;
            $data['program'] = ProgramsEnums::READER_BIRTHDAY->value;
            $data['condition'] = json_encode(['percent' => post('percent')]);

            $program = AuthorsPrograms::create($data);

            return [];
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return [];
        }
    }

    public function onConnectProgramNewReader(): RedirectResponse|array
    {
        try {
            $data['user'] = $this->user->id;
            $data['program'] = ProgramsEnums::NEW_READER->value;
            $data['condition'] = json_encode(['percent' => post('percent')]);

            $program = AuthorsPrograms::create($data);

            return [];
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return [];
        }
    }

    public function onConnectProgramRegularReader(): RedirectResponse|array
    {
        try {
            $data['user'] = $this->user->id;
            $data['program'] = ProgramsEnums::REGULAR_READER->value;
            $data['condition'] = json_encode(['percent' => post('percent')]);

            $program = AuthorsPrograms::create($data);

            return [];
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return [];
        }
    }
}
