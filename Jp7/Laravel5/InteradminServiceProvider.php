<?php

namespace Jp7\Laravel5;

use Illuminate\Support\ServiceProvider;
use Jp7\Interadmin\DynamicLoader;

class InteradminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootOrm();
    }
    
    public function bootOrm()
    {
        \InterAdminTipo::setDefaultClass(config('interadmin.namespace') . 'InterAdminTipo');
        
        spl_autoload_register([DynamicLoader::class, 'load']);
    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        \App::singleton('Jp7\Laravel5\Router', function ($app) {
            return new Router($app['router']);
        });
    }
}
