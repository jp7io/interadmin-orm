<?php

use Jp7_Interadmin_Upload_AdapterInterface as AdapterInterface;

class Jp7_Interadmin_Upload
{
    /**
     * @var AdapterInterface
     */
    protected static $adapter;

    /**
     * Altera o endereço para que aponte para a url do cliente.
     *
     * @param $url Url do arquivo.
     *
     * @return string
     */
    public static function url($path = '../../', $template = 'original')
    {
        if (static::isExternal($path)) {
            // Not an upload path => Wont change
            return $path;
        }
        $path = substr($path, strlen('../../'));
        
        if (static::isImage($path)) {
            return static::getAdapter()->imageUrl($path, $template);
        } else {
            return static::getAdapter()->url($path);
        }
    }

    public static function getHumanSize($path)
    {
        try {
            return jp7_human_size(static::getSize($path));
        } catch (RuntimeException $e) {
            return '0KB';
        }
    }

    public static function getSize($path)
    {
        if (static::isExternal($path)) {
            return;
        }
        $path = substr($path, strlen('../../'));
        return Storage::size($path);
    }

    public static function isImage($url)
    {
        return preg_match('/.(jpg|jpeg|png|gif)[#?]?[^?\/#]*$/i', $url);
    }
    
    public static function getAdapter()
    {
        global $config;
        if (!static::$adapter) {
            if ($config->imagecache === 'imgix') {
                static::$adapter = new Jp7_Interadmin_Upload_Imgix;
            } elseif ($config->imagecache) {
                static::$adapter = new Jp7_Interadmin_Upload_Intervention;
            } else {
                static::$adapter = new Jp7_Interadmin_Upload_Legacy;
            }
        }
        return static::$adapter;
    }

    public static function setAdapter(AdapterInterface $adapter)
    {
        static::$adapter = $adapter;
    }

    protected static function isExternal($path)
    {
        return !startsWith('../../upload/', $path);
    }
}
