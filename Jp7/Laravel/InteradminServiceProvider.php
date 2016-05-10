<?php

namespace Jp7\Laravel;

use Illuminate\Support\ServiceProvider;
use Jp7\Interadmin\DynamicLoader;
use Jp7\Interadmin\Type;
use Jp7\Laravel\RouterFacade as r;
use Schema;
use App;
use PDOException;
use Log;
use View;
use Route;

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
        BladeExtension::apply();
        
        $this->publishPackageFiles();
        $this->bootOrm();
        $this->shareViewPath();
        // self::bootTestingEnv();
    }
    
    private function publishPackageFiles()
    {
        $base = __DIR__.'/../..';
        
        $this->publishes([
            $base.'/config/httpcache.php' => config_path('httpcache.php'),
            $base.'/config/imgix.php' => config_path('imgix.php'),
            $base.'/config/interadmin.php' => config_path('interadmin.php'),
        ], 'config');
        
        $this->publishes([
            $base.'/resources/lang/en/interadmin.php' => resource_path('lang/en/interadmin.php'),
            $base.'/resources/lang/pt-BR/interadmin.php' => resource_path('lang/pt-BR/interadmin.php'),
        ], 'resources');
    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        App::singleton(Type::class, function () {
            $route = Route::getCurrentRoute();
            if ($route) { // Some pages don't have route, like 503.blade.php
                return r::getTypeByRoute($route);
            }
        });
    }
    
    private function bootOrm()
    {
        if (config('interadmin.namespace')) {
            Type::setDefaultClass(config('interadmin.namespace').'Type');
        }
        
        try {
            if (Schema::hasTable('tipos')) {
                DynamicLoader::register();
            }
        } catch (PDOException $e) {
            if (!App::runningInConsole()) {
                throw $e;
            }
            echo 'Interadmin DB not connected: '.$e->getMessage().PHP_EOL;
            Log::error($e);
        }
    }
    
    private function shareViewPath()
    {
        View::composer('*', function ($view) {
            $parts = explode('.', $view->getName());
            array_pop($parts);
            View::share('viewPath', implode('.', $parts));
        });
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
    /*
    private function extendFormer()
    {
        \App::before(function ($request) {
            // Needed for tests
            \Former::getFacadeRoot()->ids = [];
        });   
    }
    */
}
