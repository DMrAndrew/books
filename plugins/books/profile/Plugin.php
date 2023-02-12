<?php

namespace Books\Profile;

use Backend;
use Books\Profile\Behaviors\HasProfile;
use Books\Profile\Behaviors\Profileable;
use Books\Profile\Classes\ProfileEventHandler;
use Books\Profile\Components\Profile;
use Books\Profile\Components\ProfileNotification;
use Books\Profile\Components\ProfilePrivacy;
use Config;
use Event;
use Flash;
use Illuminate\Foundation\AliasLoader;
use RainLab\User\Controllers\Users as UsersController;
use RainLab\User\Models\User;
use Redirect;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Profile',
            'description' => 'No description provided yet...',
            'author' => 'Books',
            'icon' => 'icon-leaf',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        Event::listen('books.profile.username.modify.requested', fn ($user) => (new ProfileEventHandler())->usernameModifyRequested($user));
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        AliasLoader::getInstance()->alias('Profile', Models\Profile::class);
        Config::set('profile', Config::get('books.profile::config'));
        User::extend(function (User $model) {
            $model->implementClassWith(HasProfile::class);
        });
        foreach (config('profile.profileable') ?? [] as $class) {
            $class::extend(function ($model) {
                $model->implementClassWith(Profileable::class);
                $model->bindEvent('model.afterCreate', fn () => (new ProfileEventHandler())->createdProfilableModel($model));
                $model->bindEvent('model.afterDelete', fn () => (new ProfileEventHandler())->deletedProfilableModel($model));
            });
        }

        UsersController::extendFormFields(function ($form, $model, $context) {
            if (! $model instanceof User) {
                return;
            }
            $form->addTabFields([
                'profiles' => [
                    'type' => 'partial',
                    'path' => '$/books/profile/views/profile_relation_form.htm',
                    'tab' => 'Профили',
                ],
            ]);
            $form->removeField('avatar');
        });
        UsersController::extend(function (UsersController $controller) {
            $controller->formConfig = '$/books/user/config/config_form.yaml';
            $controller->listConfig = '$/books/user/config/config_list.yaml';
            $controller->relationConfig = '$/books/user/config/config_relation.yaml';
            $controller->implementClassWith(Backend\Behaviors\RelationController::class);

            $controller->addDynamicMethod('onChangeUsername', function ($recordId) use ($controller) {
                $model = $controller->formFindModelObject($recordId);
                $model->acceptClipboardUsername();
                Flash::success('Псевдоним пользователя успешно обновлён');

                return Redirect::refresh();
            });

            $controller->addDynamicMethod('onRejectUsername', function ($recordId) use ($controller) {
                $model = $controller->formFindModelObject($recordId);
                $model->rejectClipboardUsername();
                Flash::success('Изменение псевдонима пользователя отклонено');

                return Redirect::refresh();
            });
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            Profile::class => 'profile',
            ProfilePrivacy::class => 'profilePrivacy',
            ProfileNotification::class => 'profileNotification',
        ];
    }

    /**
     * Registers any backend permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'books.profile.some_permission' => [
                'tab' => 'Profile',
                'label' => 'Some permission',
            ],
        ];
    }

    /**
     * Registers backend navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'profile' => [
                'label' => 'Profile',
                'url' => Backend::url('books/profile/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.profile.*'],
                'order' => 500,
            ],
        ];
    }
}
