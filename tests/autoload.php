<?php
require __DIR__ . '/../vendor/autoload.php';

//load inc
$_SERVER['SERVER_ADDR'] = '::1';
$_SERVER['REMOTE_ADDR'] = '::1';
$_SERVER['HTTP_HOST'] = 'localhost';
require __DIR__ . '/../vendor/jp7internet/inc/7.lib.php';

class_alias('Jp7_Date', 'Date');

function config($key)
{
    $repository = new Illuminate\Config\Repository([
        'interadmin' => [
            'psr-4' => false,
            'preview' => true,
            'storage' => [
                // Prefix path InterAdmin saves
                'backend_path' => '../..',
                // Prefix path on the URL for the browser
                'path' => '',
                // Storage HOST
                'host' => 'example.org',
            ]
        ]
    ]);
    if (!isset($repository[$key])) {
        throw new OutOfBoundsException();
    }
    return $repository[$key];
}
