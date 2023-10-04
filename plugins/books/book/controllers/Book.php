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
         * Search by user profile
         */
        $bookTableName = (new BookModel())->getTable();
        $authorsTableName = (new Author)->getTable();
        $profileTableName = (new Profile())->getTable();

        $query->addSelect($profileTableName . '.username as profile_name');

        $query->leftJoin($authorsTableName, $authorsTableName . '.book_id', '=', $bookTableName . '.id');
        $query->leftJoin($profileTableName, $authorsTableName . '.profile_id', '=', $profileTableName . '.id');
    }
}
