<?php

declare(strict_types=1);

namespace Books\Shop\Components;

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\FileUploader\Components\ImageUploader;
use Books\Shop\Models\Category;
use Books\Shop\Models\Product;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use October\Rain\Exception\ApplicationException;
use RainLab\User\Facades\Auth;
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
    public function componentDetails(): array
    {
        return [
            'name' => 'ShopLCForm Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @return bool|\Illuminate\Http\RedirectResponse|void
     * @throws \Cms\Classes\CmsException
     * @throws DuplicateBreadcrumbException
     */
    public function init()
    {
        if ($redirect = redirectIfUnauthorized()) {
            return $redirect;
        }

        $this->registerBreadcrumbs();
        $this->prepareVars();

        /** @var ImageUploader $component */
        $component = $this->addComponent(
            ImageUploader::class,
            'productUploader',
            [
                'deferredBinding' => true,
                'imageWidth' => 250,
                'imageHeight' => 250,
                'fileTypes' => '.gif,.jpg,.jpeg,.png',
                'maxSize' => 4,
            ]
        );

        $component->bindModel('product_image', new Product());
        $this->page['titleMaxLength'] = 60;
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function onSave()
    {
        try {
            if (!Auth::check()) {
                throw new ApplicationException('Требуется авторизация');
            }

            $product = new Product();
            $data = post();

            $validator = Validator::make(
                $data,
                $product->rules + [
                    'upload_image' => 'required|accepted',
                ],
                $product->customMessages,
                $product->attributeNames
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $product->fill($validator->valid());
            $product->seller()->associate(Auth::getUser()->profile);
            $product->save(null, post('_session_key'));

            Flash::success('Товар успешно создан');

            return Redirect::to('/lc-shop');
        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }
    }

    /**
     * @return void
     * @throws DuplicateBreadcrumbException
     */
    private function registerBreadcrumbs(): void
    {
        $manager = app(BreadcrumbsManager::class);

        $manager->register('lc-shop-create', static function (BreadcrumbsGenerator $trail, $params): void {
            $trail->parent('lc');
            $trail->push('Магазин', url('/lc-shop'));
            $trail->push('Добавление товара');
        });
    }

    /**
     * @return void
     */
    private function prepareVars(): void
    {
        $this->page['categories'] = Category::all();
    }
}
