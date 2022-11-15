<?php namespace Books\Catalog\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Flash;
use Lang;
use RainLab\User\Models\User;

/**
 * Genre Backend Controller
 */
class Genre extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
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

            foreach ($checkedIds as $genreId) {
                if (!$genre = \Books\Catalog\Models\Genre::find($genreId)) {
                    continue;
                }

                switch ($bulkAction) {
                    case 'delete':
                        $genre->forceDelete();
                        break;

                    case 'activate':
                        $genre->activate();
                        break;

                    case 'deactivate':
                        $genre->deactivate();
                        break;

                    case 'favorite':
                        $genre->enableFavorite();
                        break;

                    case 'unfavorite':
                        $genre->disableFavorite();
                        break;
                }
            }

            Flash::success(Lang::get('books.catalog::lang.genres.' . $bulkAction . '_selected_success'));
        } else {
            Flash::error(Lang::get('books.catalog::lang.genres.' . $bulkAction . '_selected_empty'));
        }

        return $this->listRefresh();
    }
}
