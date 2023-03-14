<?php

namespace App;

use App\middleware\FetchCheckUp;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Cms\Classes\Controller;
use Cms\Classes\Theme;
use Exception;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\AliasLoader;
use Symfony\Component\HttpKernel\Exception\HttpException;
use System\Classes\AppBase;

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
        AliasLoader::getInstance()->alias('Carbon', Carbon::class);
        AliasLoader::getInstance()->alias('CarbonPeriod', CarbonPeriod::class);

        parent::register();
        Factory::guessFactoryNamesUsing(function ($modelName) {
            if (property_exists($modelName, 'factory')) {
                return $modelName::$factory;
            }
            throw new Exception('Factory for '.$modelName.' not found.');
        });

//        $this->app[Kernel::class]
//            ->prependMiddleware(FetchCheckUp::class);

        // Add a new middleware to end of the stack.
        $this->app[Kernel::class]
            ->pushMiddleware(FetchCheckUp::class);
    }

    /**
     * boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
//        \App::error(function (HttpException $e) {
//            $code = $e->getStatusCode();
//            $controller = new Controller(Theme::getActiveTheme());
//            $controller->setStatusCode($code);
//            return $controller->run(in_array($code, [404]) ? '/404' : '/error');
//        });
    }
}
