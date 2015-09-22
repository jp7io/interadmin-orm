<?php

namespace Jp7\Laravel;

use Route;
use Illuminate\Support\ServiceProvider;

class ImageCacheServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            $filenamePattern = '[ \w\\.\\/\\-]+';
            
            Route::get(
                'imagecache-service/clear/{filename}',
                ImageCacheController::class.'@clear'
            )->where([
                'filename' => $filenamePattern
            ]);
            
            Route::get(
                'imagecache-service/{template}/{filename}',
                ImageCacheController::class.'@create'
            )->where([
                'filename' => $filenamePattern
            ]);
        }
    }
    
    public function register()
    {
    }
}
