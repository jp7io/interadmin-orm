<?php

namespace Jp7\Laravel5;

use Illuminate\Support\ServiceProvider;

class RouterServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // Used by Jp7\Laravel5\RouterFacade
        \App::singleton(Router::class, function ($app) {
            return new Router($app['router']);
        });
    }
}
