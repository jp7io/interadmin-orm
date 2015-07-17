<?php

namespace Jp7\Laravel5;

class Cdn
{
    public static function asset($url, $version = false)
    {
        return self::replace(asset($url)).
            ($version ? '?v='.self::getVersion() : '');
    }

    public static function css($url)
    {
        return '<link href="'.self::asset($url, true).'"  rel="stylesheet" type="text/css">';
    }

    public static function js($url)
    {
        return '<script src="'.self::asset($url, true).'"></script>';
    }

    private static function replace($url)
    {
        $domain = config('app.cdn');
        
        if ($domain) {
            $url = str_replace(url(), 'http://'.$domain, $url);
        }
        
        return $url;
    }

    private static function getVersion()
    {
        // Using timestamp of the git folder as version number
        return filemtime(base_path('.git'));
    }
}
