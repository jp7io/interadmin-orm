<?php

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Config\Repository;

class InterAdminStorage extends Illuminate\Support\Facades\Storage
{
    // Temporario para usar facade sem Laravel
    protected static function resolveFacadeInstance($name)
    {
        global $config;
        static $storage;
        if (!$storage) {
            $storage = new FilesystemManager([
                'config' => new Repository([
                    'filesystems' => $config->filesystems
                ])
            ]);
        }
        return $storage;
    }
}
