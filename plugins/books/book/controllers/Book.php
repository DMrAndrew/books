<?php

namespace Books\Book\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Behaviors\RelationController;
use Backend\Classes\Controller;
use BackendMenu;
use Books\Book\Models\Author;
use Books\Book\Models\Book as BookModel;
use Books\Profile\Models\Profile;
use Flash;
use Lang;

/**
 * Book Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Book extends Controller
{
    public $implement = [
        FormController::class,
        ListController::class,
        RelationController::class
    ];

    /**
     * @var string formConfig file
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string listConfig file
     */
    public $listConfig = 'config_list.yaml';

    public $relationConfig = 'config_relation.yaml';

    /**
     * @var array required permissions
     */
    public $requiredPermissions = ['books.book.book'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Books.Book', 'book', 'book');
    }

    public function listExtendQuery($query)
    {
        /**
         * Поиск по профилям
         */
        $bookTableName = (new BookModel())->getTable();
        $authorsTableName = (new Author)->getTable();
        $profileTableName = (new Profile())->getTable();

        $query->addSelect($profileTableName . '.username as profile_name');

        $query->leftJoin($authorsTableName, $authorsTableName . '.book_id', '=', $bookTableName . '.id');
        $query->leftJoin($profileTableName, $authorsTableName . '.profile_id', '=', $profileTableName . '.id');

        $query->groupBy($bookTableName . '.id');
    }

    /**
     * @return mixed
     */
    public function onDelete()
    {
        $checkedIds = post('checked');

        if (!$checkedIds || !is_array($checkedIds) || !count($checkedIds)) {
            Flash::error(Lang::get('backend::lang.list.delete_selected_empty'));
            return $this->listRefresh();
        }

        // Create the model
        $model = new BookModel;

        $query = BookModel::query();
        $records = $query->whereIn($model->getQualifiedKeyName(), $checkedIds)->get();

        if ($records->count()) {
            foreach ($records as $record) {
                $record->delete();
            }

            Flash::success(Lang::get('backend::lang.list.delete_selected_success'));
        }
        else {
            Flash::error(Lang::get('backend::lang.list.delete_selected_empty'));
        }

        return $this->listRefresh();
    }
}
