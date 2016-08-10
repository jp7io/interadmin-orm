<?php

return [

   /*
    |--------------------------------------------------------------------------
    | HttpCache Settings
    |--------------------------------------------------------------------------
    |
    | Enable the HttpCache to cache public resources, with a shared max age (or TTL)
    | Enable ESI for edge side includes (parts that can be cached separate)
    | Set the cache to a writable dir, outside the document root.
    |
    */
    'enabled' => env('HTTPCACHE_ENABLED'),
    'esi' => false,
    'cache_dir' => storage_path('httpcache'),

    /*
     |--------------------------------------------------------------------------
     | Extra options
     |--------------------------------------------------------------------------
     |
     | Configure the default HttpCache options. See for a list of options:
     | http://symfony.com/doc/current/book/http_cache.html#symfony2-reverse-proxy
     |
     */
    'options' => [
        'debug'                  => env('APP_DEBUG'),
        'default_ttl'            => 1800,
        // Removed cookie, because laravel always sets cookies
        'private_headers'        => ['Authorization']
        /*
        'allow_reload'           => false,
        'allow_revalidate'       => false,
        'stale_while_revalidate' => 2,
        'stale_if_error'         => 60,
        */
    ],

    'blacklist' => [
        '_debugbar',
        'interadmin/*',
        // Example: 'eventos/.*/presenca',
        // Example: 'fazer-uma-viagem/seguro-de-viagem-internacional/simulador',
    ],

    'invalidate' => false //isset($_COOKIE['{{CLIENT_NAME}}']['interadmin'])
];
