<?php

namespace Jp7\HttpCache;

use Barryvdh\HttpCache\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $app = $this->app;
        $app['http_cache.store'] = $app->share(function ($app) {
            return new Store($app['http_cache.cache_dir']);
        });

        $stack = app('Barryvdh\StackMiddleware\StackMiddleware');

        $stack->bind(
            'Jp7\HttpCache\CacheRequests',
            HttpCacheExtension::class,
            [
                \App::make('http_cache.store'),
                \App::make('http_cache.esi'),
                \App::make('http_cache.options')
            ]
        );
    }
}
