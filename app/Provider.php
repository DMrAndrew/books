<?php

namespace App;

use App\classes\CustomPaginator;
use App\classes\RevisionHistory;
use App\middleware\FetchCheckUp;
use App\traits\DateScopes;
use App\traits\FileExtension;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\DatabaseManager;
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

    protected array $middlewares = [
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        FetchCheckUp::class,
    ];

    protected array $implements = [
        Model::class => DateScopes::class,
        File::class => FileExtension::class,
        Revision::class => RevisionHistory::class
    ];

    protected array $aliases = [
        'Carbon' => Carbon::class,
        'CarbonPeriod' => CarbonPeriod::class,
        'CustomPaginator' => CustomPaginator::class
    ];

    protected array $commands = [
        'model:prune' => PruneCommand::class,
        'queue:batches-table' => BatchesTableCommand::class
    ];


    /**
     * register method, called when the app is first registered.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
        $this->app->rebinding('request', function ($app, $request) {
            $request->setUserResolver(fn() => Auth::getUser());
        });
        $this->app->singleton(DatabaseManager::class, fn($app) => $app->make('db'));
        $this->app->singleton(GateContract::class, static function ($app): GateContract {
            return new Gate($app, static function () use ($app) {
                return fn() => Auth::getUser();
            });
        });


        array_walk($this->commands, fn($class, $command) => $this->registerConsoleCommand($command, $class));

        array_walk($this->middlewares, fn($middleware) => $this->app[Kernel::class]->pushMiddleware($middleware));

        loadAlias($this->aliases);

        loadImplements($this->implements);
    }

    /**
     * boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        Model::preventLazyLoading(!app()->isProduction());
        Request::setTrustedProxies(config('app.trusted_proxies'), -1);
    }
}
