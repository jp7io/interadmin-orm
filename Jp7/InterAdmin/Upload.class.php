<?php

use League\Url\Url;

class Jp7_InterAdmin_Upload
{
    public static $legacyTemplates = [
                    'thumb_interadmin' => '40x40',
                ];

    public static function isImage($path)
    {
        return preg_match('/.(jpg|jpeg|png|gif)[#?]?[^?\/#]*$/i', $path);
    }

    public static function imageCache()
    {
        global $config;
        return $config->imagecache;
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
        $url = Url::createFromUrl($config->storage['host']);
        $url->setScheme('http');
        list($path, $query) = explode('?', $path);
        $url->setPath($path);
        $url->setQuery($query);
        return $url;
    }

    // No upload[url] setting / Legacy
    public static function defaultUrl($path)
    {
        global $config;
        $url = Url::createFromUrl($config->server->host);
        $url->setScheme('http');
        return $url->setPath($config->name_id . '/' . $path);
    }

    public static function uselegacyTemplate($path, $template = 'original')
    {
        if ($template != 'original') {
            $legacyPath = Url::createFromUrl($path);
            $legacyPath->getQuery()->modify(['size' => $legacyTemplates[$template]]);
            $path = $legacyPath->getRelativeUrl();
        }
        return $path;
    }

    public static function useImageTemplate($path, $template = 'original')
    {
        if (self::imageCache()) {
            return jp7_replace_beginning('upload/', 'imagecache/' . $template . '/', $path);
        }

        return self::uselegacyTemplate($path, $template);
    }

    public static function storagePath($path, $template = 'original')
    {
        $path = jp7_replace_beginning('../../', '', $path); // Remove '../../'

        if (self::isImage($path)) {
            $path = self::useImageTemplate($path, $template);
        }

        return $path;
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

        $path = self::storagePath($path, $template);

        if (self::hasExternalStorage()) {
            $url = self::storageUrl($path);
        } else {
            // legacy
            $url = self::defaultUrl($path);
        }
        
        return $url->__toString();
    }
}
