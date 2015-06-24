<?php

namespace Jp7\Laravel5;

use Illuminate\Support\ServiceProvider;
use Jp7\Interadmin\DynamicLoader;

/*
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Jp7\ExceptionHandler;
*/

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
        
        BladeExtension::apply();
        // self::bootTestingEnv();
        
        $this->extendFormer();
        $this->extendView();
        
        //self::clearInterAdminCache();
    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        \App::singleton(Router::class, function ($app) {
            return new Router($app['router']);
        });
    }
    
    private function bootOrm()
    {
        \InterAdminTipo::setDefaultClass(config('interadmin.namespace') . 'InterAdminTipo');
        
        if (\Schema::hasTable('_tipos')) {
            spl_autoload_register([DynamicLoader::class, 'load']);
        }
    }
    /*
    private function bootTestingEnv()
    {
        if (\App::environment('testing')) {
            // Filters are disabled by default
            \Route::enableFilters();

            // Bug former with phpunit
            if (!\Request::hasSession()) {
                \Request::setSession(\App::make('session.store'));
            }
        }
    }
    */
    private function extendFormer()
    {
        /*
        \App::before(function ($request) {
            // Needed for tests
            \Former::getFacadeRoot()->ids = [];
        });
        */
        
        if (!class_exists('Former')) {
            return;
        }
       
        \Former::framework('TwitterBootstrap3');
        \Former::setOption('default_form_type', 'vertical');

        if ($dispatcher = \App::make('former.dispatcher')) {
            $dispatcher->addRepository('Jp7\\Former\\Fields\\');
        }
    }

    private function extendView()
    {
        \View::composer('*', function ($view) {
            $parts = explode('.', $view->getName());
            array_pop($parts);
            \View::share('viewPath', implode('.', $parts));
        });
    }
    /*
    private static function clearInterAdminCache()
    {
        if (!\App::environment('local')) {
            return;
        }
        // Atualiza classmap e routes com CMD+SHIFT+R ou no terminal
        if (PHP_SAPI === 'cli' || \Request::server('HTTP_CACHE_CONTROL') === 'no-cache') {
            \Cache::forget('Interadmin.routes');
            \Cache::forget('Interadmin.classMap');
        }
    }*/
}
