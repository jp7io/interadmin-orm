<?php

use \Illuminate\Cache\FileStore;
use \Illuminate\Cache\Repository;
use \Illuminate\Filesystem\Filesystem;

class InterAdminCacheFacade extends Illuminate\Support\Facades\Cache
{
    // Temporario para usar facade sem Laravel
    protected static function resolveFacadeInstance($name)
    {
        static $cache;
        if (!$cache) {
            $filestore = new FileStore(new Filesystem(), BASE_PATH.'/cache');
            $cache = new Repository($filestore);
        }
        return $cache;
    }
}
