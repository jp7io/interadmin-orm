<?php

use Illuminate\Filesystem\FilesystemManager;

class Storage extends Illuminate\Support\Facades\Storage
{
    
    // Temporario para usar facade sem Laravel
    protected static function resolveFacadeInstance($name)
    {
        $app = [
            'config' => self::interadminConfig()
        ];
        return new FilesystemManager($app);
    }

    private static function interadminConfig()
    {
        global $config;
        if (!isset($config->filesystems)) {
            $config->filesystems = self::defaultInteradminConfig();
        }
        return $config->filesystems;
    }

    private static function defaultInteradminConfig()
    {
        global $c_cliente_physical_path, $c_remote, $c_cliente_remote_path, $config;
        $ftp_path = $config->server->ftp_path ? $config->server->ftp_path : '/web';

        if ($c_remote) {
            return [
                'filesystems.default' => 'ftp',
                'filesystems.disks.ftp' => [
                    'driver'   => 'ftp',
                    'host'     => $config->server->ftp,
                    'username' => $config->server->user,
                    'password' => $config->server->pass,
                    'root'     => $ftp_path . '/' . $c_cliente_remote_path
                ],
            ];
        } else {
            return [
                'filesystems.default' => 'local',
                'filesystems.disks.local' => [
                    'driver' => 'local',
                    'root'   => $c_cliente_physical_path,
                ]
            ];
        }
    }
}
