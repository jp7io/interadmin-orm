<?php

$defaultAppHost = parse_url(config('app.url'), PHP_URL_HOST);

return [
    // Prefix of the class: Client_Record, Client_Type
    'namespace' => ucfirst(config('app.name')).'_',
    // Whether records need the field "publish" checked
    'preview' => true,
    // Options for uploaded files:
    'storage' => [
        // Prefix path InterAdmin saves
        'backend_path' => '../..',
        // Prefix path on the URL for the browser
        'path' => '',
        // Storage HOST
        'host' => env('STORAGE_HOST', $defaultAppHost),
        'scheme' => 'https',
    ],
    // Which host Interadmin can be found on
    'host' => env('INTERADMIN_HOST', 'interadmin.jp7.com.br')
];
