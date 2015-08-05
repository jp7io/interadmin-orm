<?php

use League\Url\Url;

class Jp7_InterAdmin_Upload
{

    // Has upload[url] set on config (S3 or Local)
    public static function hasExternalUpload()
    {
        global $config;
        return $config->upload && $config->upload['url'];
    }

    public static function uploadUrl($path)
    {
        global $config;
        $url = Url::createFromUrl($config->upload['url']);
        $url->setScheme('http');
        $url->setPath($path);
        return $url;
    }

    // No upload[url] setting
    public static function defaultUrl($path)
    {
        global $config;
        $url = Url::createFromUrl($config->server->host);
        $url->setScheme('http');
        return $url->setPath($config->name_id . '/' . $path);
    }

    public static function useImageTemplate($path, $template = 'original')
    {
        return str_replace('upload/', 'imagecache/' . $template . '/', $path);
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
        $path = str_replace('../../', '', $path); // Remove '../../'

        $path = self::useImageTemplate($path, $template);

        if (!startsWith('http://', $url)) {
            if (self::hasExternalUpload()) {
                $url = self::uploadUrl($path);
            } else {
                $url = self::defaultUrl($path);
            }
        } else { // Absolute URL => Wont change
            $url = Url::createFromUrl($path);
        }

        return $url->__toString();
    }
}
