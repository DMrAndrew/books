<?php namespace Books\Book\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Books\Book\Models\Content as ContentModel;
use Flash;
use Redirect;

/**
 * Content Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Content extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    /**
     * @var string formConfig file
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string listConfig file
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var array required permissions
     */
    public $requiredPermissions = ['books.book.content'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Books.Book', 'book', 'content');
    }

    public function listExtendQuery($query)
    {
        $query->notRegular()
            ->with('contentable.edition.book')
            ->orderByDesc('updated_at');
    }

    public function onAccept($recordId = null){
        if ($model = ContentModel::find($recordId)) {
            $model->contentable?->service()?->mergeDeferred($this->getPostComment());
            Flash::success('Сохранено');

            return Redirect::refresh();
        }

        return Flash::error('Контент не найден');
    }

    public function onReject($recordId = null){

        if ($model = ContentModel::find($recordId)) {
            $model->service()->markRejected($this->getPostComment());
            Flash::success('Сохранено');

            return Redirect::refresh();
        }

        return Flash::error('Контент не найден');
    }

    public function getPostComment(){
        return post('Content.new_comment');
    }
}
