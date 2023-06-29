<?php

namespace App;

use App\classes\RevisionHistory;
use App\middleware\FetchCheckUp;
use App\traits\DateScopes;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Queue\Console\BatchesTableCommand;
use Model;
use Request;
use System\Classes\AppBase;
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
        $this->registerConsoleCommand('model:prune', PruneCommand::class);
        $this->registerConsoleCommand('queue:batches-table', BatchesTableCommand::class);
        AliasLoader::getInstance()->alias('Carbon', Carbon::class);
        AliasLoader::getInstance()->alias('CarbonPeriod', CarbonPeriod::class);

        Factory::guessFactoryNamesUsing(function ($modelName) {
            if (property_exists($modelName, 'factory')) {
                return $modelName::$factory;
            }
            throw new Exception('Factory for ' . $modelName . ' not found.');
        });
        Revision::extend(function (Revision $revision) {
            $revision->implementClassWith(RevisionHistory::class);
        });

//        $this->app[Kernel::class]
//            ->prependMiddleware(FetchCheckUp::class);

        // Add a new middleware to end of the stack.
        $this->app[Kernel::class]
            ->pushMiddleware(TrimStrings::class);
        $this->app[Kernel::class]
            ->pushMiddleware(ConvertEmptyStringsToNull::class);
        $this->app[Kernel::class]
            ->pushMiddleware(FetchCheckUp::class);
        Model::extend(function (Model $model) {
            $model->implementClassWith(DateScopes::class);
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
        Model::preventLazyLoading(!app()->isProduction());
        Request::setTrustedProxies(config('app.trusted_proxies'), -1);
    }
}
