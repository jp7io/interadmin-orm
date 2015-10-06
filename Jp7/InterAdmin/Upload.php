<?php

use Jp7_InterAdmin_Upload_AdapterInterface as AdapterInterface;

class Jp7_InterAdmin_Upload
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
        if (!startsWith('../../upload/', $path)) {
            // Not an upload path => Wont change
            return $path;
        }
        $path = substr($path, strlen('../../'));
        
        if (static::isImage($path)) {
            return static::getAdapter()->url($path, $template);
        } else {
            global $config;
            return $config->storage['host'].'/'.$path;
        }
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
                static::$adapter = new Jp7_InterAdmin_Upload_Imgix;
            } elseif ($config->imagecache) {
                static::$adapter = new Jp7_InterAdmin_Upload_Intervention;
            } else {
                static::$adapter = new Jp7_InterAdmin_Upload_Legacy;
            }
        }
        return static::$adapter;
    }

    public static function setAdapter(AdapterInterface $adapter)
    {
        static::$adapter = $adapter;
    }
}
