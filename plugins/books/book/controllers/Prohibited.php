<?php namespace Books\Book\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use BackendMenu;
use Backend\Classes\Controller;
use Books\Book\Classes\Scopes\ProhibitedScope;
use October\Rain\Database\Builder;

/**
 * Prohibited Backend Controller
 *
 * @link https://docs.octobercms.com/3.x/extend/system/controllers.html
 */
class Prohibited extends Controller
{
    public $implement = [
        FormController::class,
        ListController::class,
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
    public $requiredPermissions = ['books.book.prohibited'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Books.Book', 'book', 'prohibited');
    }

    public function listExtendQuery($query)
    {
        $query->with(['prohibitable']);
    }

}
