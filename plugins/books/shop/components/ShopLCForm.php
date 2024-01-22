<?php namespace Books\Shop\Components;

use Backend\Controllers\Auth;
use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Certificates\Models\CertificateTransactions;
use Books\FileUploader\Components\ImageUploader;
use Books\Shop\Models\Category;
use Books\Shop\Models\Product;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use Redirect;
use ValidationException;
use Validator;

/**
 * ShopLCForm Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class ShopLCForm extends ComponentBase
{
    private $user;

    public function componentDetails()
    {
        return [
            'name' => 'ShopLCForm Component',
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
        $this->user = \RainLab\User\Facades\Auth::getUser() ?? throw new ApplicationException('User required');
        $component = $this->addComponent(
            ImageUploader::class,
            'productUploader',
            [
                'modelClass' => Product::class,
                'modelKeyColumn' => 'image',
                'deferredBinding' => true,
                'imageWidth' => 250,
                'imageHeight' => 250,

            ]
        );
        $component->bindModel('product_image', new Product());
        $this->prepareVals();
        $this->registerBreadcrumbs();
    }

    private function prepareVals()
    {
        $this->page['categories'] = Category::all();
    }

    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-shop-create', function (BreadcrumbsGenerator $trail, $params) {
            $trail->parent('lc');
            $trail->push('Магазин', url('/lc-shop'));
            $trail->push('Добавление товара');
        });
    }

    public function getSessionKey()
    {
        return post('_session_key');
    }

    public function onSave()
    {
        try {
            $postData = collect(post());

            $validator = Validator::make(
                $postData->toArray(),
                collect((new Product())->rules)->only([
                    'title', 'description', 'price', 'quantity', 'category_id'
                ])->toArray(),
                collect((new Product())->customMessages)->only([
                    'title', 'description', 'price', 'quantity', 'category_id'
                ])->toArray(),
                collect((new Product())->attributeNames)->only([
                    'title', 'description', 'price', 'quantity', 'category_id'
                ])->toArray()
            );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $data = [
                'title' => $postData['title'],
                'description' => $postData['description'],
                'price' => $postData['price'],
                'quantity' => $postData['quantity'],
                'category_id' => $postData['category_id'],
                'seller_id' => $this->user->id
            ];

            $product = new Product();
            $product->fill($data)->save();
            $product->save(null, $this->getSessionKey());

            Flash::success('Товар успешно создан');
            return Redirect::to('/lc-shop');

        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }
    }
}
