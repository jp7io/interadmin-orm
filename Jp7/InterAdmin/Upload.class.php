<?php

use League\Url\Url;
use League\Url\Components\Path;
use League\Url\Components\Query;

class Jp7_InterAdmin_Upload
{
    public static $legacyTemplates = [
                    'thumb_interadmin' => '40x40',
                ];

    public static function isImage($url)
    {
        return preg_match('/.(jpg|jpeg|png|gif)[#?]?[^?\/#]*$/i', $url);
    }

    public static function imageCache()
    {
        global $config;
        return isset($config->imagecache) && $config->imagecache;
    }

    // Has upload[url] set on config (S3 or Local)
    public static function hasExternalStorage()
    {
        global $config;
        return $config->storage && $config->storage['host'];
    }

    public static function storageUrl($path)
    {
        global $config;
        return self::createUrl($config->storage['host'], $path);
    }

    // No upload[url] setting / Legacy
    public static function legacytUrl($path)
    {
        global $config;
        $url = self::createUrl($config->server->host, $path);
        $path = $url->getPath();
        $path->prepend($config->name_id);
        return $url;
    }

    public static function uselegacyTemplate($url, $template = 'original')
    {
        $legacyTemplate = self::$legacyTemplates[$template];
        if (isset($legacyTemplate)) {
            $url->getQuery()->modify(['size' => $legacyTemplate]);
        }
        return $url;
    }

    public static function useImageTemplate($url, $template = 'original')
    {
        if (self::imageCache()) {
            $path = $url->getPath();
            $path->remove('upload');
            $path->prepend('imagecache/' . $template);
            return $url;
        }

        return self::uselegacyTemplate($url, $template);
    }

    public static function storagePath($url, $template = 'original')
    {
        $path = $url->getPath();
        // Remove '../../'
        $path->remove('..');
        $path->remove('..');

        if (self::isImage($url->__toString())) {
            $url = self::useImageTemplate($url, $template);
        }
        return $url;
    }

    public static function createUrl($host, $path)
    {
        $url = Url::createFromUrl($host);
        $url->setScheme('http');
        list($path, $query) = explode('?', $path);
        $url->setPath($path);
        $url->setQuery($query);
        return $url;
    }

    /**
     * Altera o endereço para que aponte para a url do cliente.
     *
     * @param $url Url do arquivo.
     *
     * @return string
     */
    public static function url($path = '../../', $template = 'original')
    {
        if (!startsWith('../../', $path)) {
            // Not an upload path => Wont change
            return $path;
        }

        if (self::hasExternalStorage()) {
            $url = self::storageUrl($path);
        } else {
            $url = self::legacytUrl($path);
        }
        $url = self::storagePath($url, $template);
        return $url->__toString();
    }
}
