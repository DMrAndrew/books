<?php

namespace Books\Chat;

use App\middleware\SetMessengerProvider;
use Backend;
use Books\Chat\Classes\MessengerService;
use Books\Chat\Components\Messenger;
use Books\Profile\Models\Profile;
use Broadcast;
use Event;
use Illuminate\Validation\ValidationException;
use RainLab\User\Classes\AuthMiddleware;
use RainLab\User\Facades\Auth;
use RTippin\Messenger\Http\Collections\GroupThreadCollection;
use RTippin\Messenger\Models\Thread;
use System\Classes\PluginBase;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = ['Books.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Chat',
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
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        Route::get('/block/{provider_id}', function ($provider_id) {
            if (!$provider_id) {
                throw new ValidationException('нужен провайдер');
            }
            Auth::getUser()?->profile->blackListChatFor(Profile::find($provider_id));
            return response()->json();
        })->middleware(['web', AuthMiddleware::class, SetMessengerProvider::class])
            ->prefix('api/messenger');


        Route::get('/threads/search/{query?}', function ($q, \RTippin\Messenger\Messenger $messenger) {

            return new GroupThreadCollection(Thread::hasProvider($messenger->getProvider())
                ->where(function ($builder) use ($q) {
                    return $builder->where('subject', 'LIKE', "%{$q}%")->orWhereHas('participants',
                        fn($query) => $query->whereHas('owner', fn($b) => $b->username($q)));
                })
                ->with([
                    'participants.owner',
                    'latestMessage.owner',
                ])
                ->get());
        })
            ->middleware(['web', AuthMiddleware::class, SetMessengerProvider::class])
            ->prefix('api/messenger');
        Event::listen(MessengerService::updatableEvents(), fn($e) => MessengerService::broadcastUpdate($e));
        Broadcast::routes(['web', AuthMiddleware::class, SetMessengerProvider::class]);
        require_once __DIR__.'/channels.php';
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            Messenger::class => 'Messenger',
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
            'books.chat.some_permission' => [
                'tab' => 'Chat',
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
            'chat' => [
                'label' => 'Chat',
                'url' => Backend::url('books/chat/mycontroller'),
                'icon' => 'icon-leaf',
                'permissions' => ['books.chat.*'],
                'order' => 500,
            ],
        ];
    }
}
