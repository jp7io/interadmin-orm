<?php

namespace Jp7\Laravel;

use Illuminate\Support\ServiceProvider;
use Jp7\Interadmin\DynamicLoader;
use Jp7\Interadmin\Type;
use Schema;
use App;
use PDOException;
use Log;
use View;

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
        
        $this->bootOrm();
        $this->shareViewPath();
        // self::bootTestingEnv();
    }
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        
    }
    
    private function bootOrm()
    {
        Type::setDefaultClass(config('interadmin.namespace').'Type');
        
        try {
            if (Schema::hasTable('tipos')) {
                spl_autoload_register([DynamicLoader::class, 'load']);
            }
        } catch (PDOException $e) {
            if (App::runningInConsole()) {
                echo $e->getMessage() . PHP_EOL;
            }
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
