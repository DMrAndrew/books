<?php

namespace Books\Profile;

use Backend;
use Books\Profile\Behaviors\HasProfile;
use Books\Profile\Behaviors\Masterable;
use Books\Profile\Behaviors\Slavable;
use Books\Profile\Classes\ProfileEventHandler;
use Books\Profile\Components\AuthorSpace;
use Books\Profile\Components\NotificationLC;
use Books\Profile\Components\PrivacyLC;
use Books\Profile\Components\Profile;
use Books\Profile\Components\ProfileLC;
use Books\Profile\Components\Subs;
use Books\Profile\Models\Profile as ProfileModel;
use Books\Profile\Models\Profiler;
use Config;
use Event;
use Flash;
use Illuminate\Foundation\AliasLoader;
use October\Rain\Database\Model;
use RainLab\User\Controllers\Users as UsersController;
use RainLab\User\Models\User;
use Redirect;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'RainLab.User', 'Books.User',
    ];

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
    public function register(): void
    {
        Event::listen('books.profile.username.modify.requested', fn($user) => (new ProfileEventHandler())->usernameModifyRequested($user));
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot(): void
    {
        AliasLoader::getInstance()->alias('Profile', ProfileModel::class);
        AliasLoader::getInstance()->alias('Profiler', Profiler::class);
        Config::set('profile', Config::get('books.profile::config'));

        User::extend(function (User $model) {
            $model->implementClassWith(HasProfile::class);
        });

        foreach ([User::class, ProfileModel::class] as $class) {
            $class::extend(function (Model $model) {
                $model->implementClassWith(Masterable::class);
            });
        }

        foreach (config('profile.slavable') ?? [] as $class) {
            $class::extend(function ($model) {
                $model->implementClassWith(Slavable::class);
            });
        }

        UsersController::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof User) {
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

            $controller->addDynamicMethod('onChangeUsername', function ($recordId) {
                $model = ProfileModel::find(post('manage_id'));
                if ($model) {
                    $model->acceptClipboardUsername();
                    Flash::success('Псевдоним пользователя успешно обновлён');

                    return Redirect::refresh();
                }

                return Flash::error('Профиль не найден');
            });

            $controller->addDynamicMethod('onRejectUsername', function ($recordId) {
                $model = ProfileModel::find(post('manage_id'));
                if ($model) {
                    $model->rejectClipboardUsername();
                    Flash::success('Изменение псевдонима пользователя отклонено');

                    return Redirect::refresh();
                }
            });
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents(): array
    {
        return [
            Profile::class => 'profile',
            ProfileLC::class => 'profileLC',
            PrivacyLC::class => 'privacyLC',
            NotificationLC::class => 'notificationLC',
            AuthorSpace::class => 'author_space',
            Subs::class => 'subs',
        ];
    }
}
