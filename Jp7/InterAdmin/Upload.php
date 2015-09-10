<?php

use League\Url\Url;
use League\Url\Components\Path;
use League\Url\Components\Query;

class Jp7_InterAdmin_Upload
{
    public static $legacyTemplates = [
        'thumb_interadmin' => '40x40'
    ];
    
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
        
        if (self::hasExternalStorage()) {
            $url = self::storageUrl($path);
        } else {
            $url = self::legacyUrl($path);
        }
        $url = self::storagePath($url, $template);
        return $url->__toString();
    }

    public static function isImage($url)
    {
        return preg_match('/.(jpg|jpeg|png|gif)[#?]?[^?\/#]*$/i', $url);
    }

    public static function imageCache()
    {
        global $config;
        return !empty($config->imagecache);
    }

    // Has upload[url] set on config (S3 or Local)
    protected static function hasExternalStorage()
    {
        global $config;
        return $config->storage && $config->storage['host'];
    }

    protected static function storageUrl($path)
    {
        global $config;
        return self::createUrl($config->storage['host'], $path);
    }

    // No upload[url] setting / Legacy
    protected static function legacyUrl($path)
    {
        global $config, $jp7_app;
        $url = self::createUrl($config->server->host, $path);
        $path = $url->getPath();
        if ($jp7_app != 'interadmin') {
            $path->prepend($jp7_app);
        }
        $path->prepend($config->server->path);
        
        return $url;
    }

    protected static function useLegacyTemplate($url, $template = 'original')
    {
        $legacyTemplate = self::$legacyTemplates[$template];
        if (isset($legacyTemplate)) {
            $url->getQuery()->modify([
                'size' => $legacyTemplate
            ]);
        }
        return $url;
    }

    protected static function useImageTemplate($url, $template = 'original')
    {
        if (self::imageCache()) {
            $path = $url->getPath();
            $path->remove('upload');
            $path->prepend('imagecache/' . $template);
            return $url;
        }

        return self::useLegacyTemplate($url, $template);
    }

    protected static function storagePath($url, $template = 'original')
    {
        if (self::isImage($url->__toString())) {
            $url = self::useImageTemplate($url, $template);
        }
        return $url;
    }

    protected static function createUrl($host, $path)
    {
        $url = Url::createFromUrl($host);
        $url->setScheme('http');
        list($path, $query) = explode('?', $path);
        $url->setPath($path);
        $url->setQuery($query);
        return $url;
    }
}
