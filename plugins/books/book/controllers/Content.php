<?php namespace Books\Book\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Books\Book\Models\Content as ContentModel;

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
        $query->deferred()
            ->with('contentable.edition.book')
            ->orderBy('requested_at', 'desc');
    }
}
