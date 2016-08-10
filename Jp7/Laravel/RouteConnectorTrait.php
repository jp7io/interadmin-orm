<?php

namespace Jp7\Laravel;

use Illuminate\Routing\Router;
use Jp7\Interadmin\DynamicLoader;
use Jp7\Laravel\RouterFacade as r;

trait RouteConnectorTrait
{
    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @param \Illuminate\Routing\Router $router
     */
    protected function mapWebRoutes(Router $router)
    {
        if (!DynamicLoader::isRegistered()) {
            $this->showDbNotConnected();
            return;
        }
        // Clear Interadmin route map - allows route:cache to work
        r::clearCache();

        // Normal Laravel routing
        $router->group([
            'namespace' => $this->namespace, 'middleware' => 'web',
        ], function ($router) {
            require app_path('Http/routes.php');
        });

        // Save new Interadmin route map
        r::saveCache();
    }

    protected function showDbNotConnected()
    {
        $errorMsg = 'InterAdmin not connected. Possible causes:
            1 - nano .env
                => Check DB_HOST, DB_USERNAME, ...
            2 - nano config/app.php
                => Make sure InteradminServiceProvider is before RouteServiceProvider';
        if (!\App::runningInConsole()) {
            throw new \Exception($errorMsg);
        }
        // Exception is not thrown because artisan commands would stop working
        echo '[Skipped routes] '.$errorMsg.PHP_EOL;
    }
}
