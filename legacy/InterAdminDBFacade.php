<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class InterAdminDBFacade extends Illuminate\Support\Facades\DB
{
    protected static function resolveFacadeInstance($name)
    {
        global $config;
        static $db;
        if (!$db) {
            $db = new Capsule;
            $db->setAsGlobal();
            $db->addConnection([
                'driver'    => 'mysql',
                'host'      => $config->db->host,
                'database'  => $config->db->name,
                'username'  => $config->db->user,
                'password'  => $config->db->pass,
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => $config->db->prefix.'_',
            ]);
        }
        return $db;
    }
}
