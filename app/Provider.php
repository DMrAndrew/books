<?php

namespace App;

use App\classes\CustomPaginator;
use App\classes\RevisionHistory;
use App\middleware\FetchCheckUp;
use App\traits\DateScopes;
use App\traits\FileExtension;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Queue\Console\BatchesTableCommand;
use Model;
use RainLab\User\Facades\Auth;
use Request;
use System\Classes\AppBase;
use System\Models\File;
use System\Models\Revision;

/**
 * Provider is an application level plugin, all registration methods are supported.
 */
class Provider extends AppBase
{
    /**
     * register method, called when the app is first registered.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
        $this->app->rebinding('request', function ($app, $request) {
            $request->setUserResolver(function () use ($app) {
                return Auth::getUser();
            });
        });
        $this->app->singleton(DatabaseManager::class, function ($app) {
            return $app->make('db');
        });
        $this->app->singleton(GateContract::class, static function ($app): GateContract {
            return new Gate($app, static function () use ($app) {
                return fn() => Auth::getUser();
            });
        });
        $this->registerConsoleCommand('model:prune', PruneCommand::class);
        $this->registerConsoleCommand('queue:batches-table', BatchesTableCommand::class);
        AliasLoader::getInstance()->alias('Carbon', Carbon::class);
        AliasLoader::getInstance()->alias('CarbonPeriod', CarbonPeriod::class);
        AliasLoader::getInstance()->alias('CustomPaginator', CustomPaginator::class);

        Factory::guessFactoryNamesUsing(function ($modelName) {
            if (property_exists($modelName, 'factory')) {
                return $modelName::$factory;
            }
            throw new Exception('Factory for '.$modelName.' not found.');
        });
        Revision::extend(function (Revision $revision) {
            $revision->implementClassWith(RevisionHistory::class);
        });

        // Add a new middleware to end of the stack.
        $middlewares = [
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
            FetchCheckUp::class,
        ];

        foreach ($middlewares as $middleware) {

            $this->app[Kernel::class]->pushMiddleware($middleware);
        }

        Model::extend(function (Model $model) {
            $model->implementClassWith(DateScopes::class);
        });
        File::extend(function (File $model) {
            $model->implementClassWith(FileExtension::class);
        });
    }

    /**
     * boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        Model::preventLazyLoading(! app()->isProduction());
        Request::setTrustedProxies(config('app.trusted_proxies'), -1);
    }
}
