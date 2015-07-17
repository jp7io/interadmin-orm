<?php

namespace Jp7\Laravel5;

class Cdn
{
    public static function asset($url)
    {
        return self::replace(asset($url)) .'?v='.self::getVersion();
    }

    public static function css($url)
    {
        return '<link href="'.self::asset($url).'"  rel="stylesheet" type="text/css">';
    }

    public static function js($url)
    {
        return '<script src="'.self::asset($url).'"></script>';
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
