<?php

return [
    'host' => env('IMGIX_HOST', false),
    'templates' => [
        'thumb-interadmin' => ['w' => 40, 'h' => 40, 'fit' => 'crop'],
    ],
];
