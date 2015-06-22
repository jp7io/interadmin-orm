<?php

namespace Jp7\Laravel;

class Cdn
{
    public static function asset($url)
    {
        return self::replace(asset($url));
    }

    public static function css($url)
    {
        return '<link href="'.self::asset($url).'?v='.self::getVersion().'"  rel="stylesheet" type="text/css">';
    }

    public static function js($url)
    {
        return '<script src="'.self::asset($url).'?v='.self::getVersion().'"></script>';
    }

    private static function replace($url)
    {
        $config = \InterSite::config();
        
        if (!empty($config->cdn_domain)) {
            $url = str_replace(url(), 'http://'.$config->cdn_domain, $url);
        }
        
        return $url;
    }

    private static function getVersion()
    {
        // Using timestamp of the services.json as version number
        return filemtime(storage_path('meta/services.json'));
    }
}
