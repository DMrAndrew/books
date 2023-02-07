<?php namespace Books\Catalog\Controllers;

use Lang;
use Flash;
use BackendMenu;
use Backend\Classes\Controller;
use Books\Catalog\Models\Genre as GenreModel;
use Backend\Behaviors\ListController;
use Backend\Behaviors\FormController;

/**
 * Genre Backend Controller
 */
class Genre extends Controller
{
    public $implement = [
        FormController::class,
        ListController::class
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
    public $requiredPermissions = ['books.catalog.genre'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Books.Catalog', 'catalog', 'genre');
    }

    public function index()
    {
        $this->addJs('/plugins/rainlab/user/assets/js/bulk-actions.js');

        $this->asExtension('ListController')->index();
    }

    /**
     * Perform bulk action on selected users
     */
    public function index_onBulkAction()
    {
        if (
            ($bulkAction = post('action')) &&
            ($checkedIds = post('checked')) &&
            is_array($checkedIds) &&
            count($checkedIds)
        ) {

            $allowed = ['delete', 'activate', 'deactivate', 'enableFavorite', 'disableFavorite', 'checkAdult', 'uncheckAdult'];
            if (in_array($bulkAction, $allowed)) {
                GenreModel::query()
                    ->whereIn('id', $checkedIds)
                    ->get()
                    ->map
                    ->{$bulkAction}();
            }
            Flash::success(Lang::get('books.catalog::lang.genres.' . $bulkAction . '_selected_success'));
        } else {
            Flash::error(Lang::get('books.catalog::lang.genres.' . $bulkAction . '_selected_empty'));
        }

        return $this->listRefresh();
    }
}
