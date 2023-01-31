<?php namespace App;

use Cms\Classes\Controller;
use Cms\Classes\Theme;
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
        parent::register();
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
