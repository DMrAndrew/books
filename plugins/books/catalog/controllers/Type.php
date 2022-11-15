<?php namespace Books\Catalog\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Flash;
use Lang;

/**
 * Type Backend Controller
 */
class Type extends Controller
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
    public $requiredPermissions = ['books.catalog.type'];

    /**
     * __construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Books.Catalog', 'catalog', 'type');
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

            foreach ($checkedIds as $typeId) {
                if (!$type = \Books\Catalog\Models\Type::find($typeId)) {
                    continue;
                }

                switch ($bulkAction) {
                    case 'delete':
                        $type->forceDelete();
                        break;

                    case 'activate':
                        $type->activate();
                        break;

                    case 'deactivate':
                        $type->deactivate();
                        break;

                }
            }

            Flash::success(Lang::get('books.catalog::lang.types.' . $bulkAction . '_selected_success'));
        } else {
            Flash::error(Lang::get('books.catalog::lang.types.' . $bulkAction . '_selected_empty'));
        }

        return $this->listRefresh();
    }
}
