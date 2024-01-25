<?php

declare(strict_types=1);

namespace Books\Shop\Components;

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Books\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use Books\Shop\Models\Category;
use Books\Shop\Models\Product;
use Cms\Classes\ComponentBase;
use Exception;
use Flash;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Input;
use RainLab\User\Facades\Auth;
use Redirect;
use System\Models\File;
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
            $data = Input::all();

            $validator = Validator::make(
                $data,
                $product->rules,
                $product->customMessages,
                $product->attributeNames
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            if (!Input::hasFile('product_image')) {
                throw new ApplicationException($product->customMessages['product_image.required']);
            }

            $file = new File;
            $file->data = Input::file('product_image');
            $file->is_public = true;
            $file->save();

            $product->product_image()->add($file);

            $product->fill($validator->valid());
            $product->seller()->associate(Auth::getUser()->profile);
            $product->save();

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
