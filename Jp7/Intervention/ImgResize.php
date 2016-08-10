<?php

namespace Jp7\Intervention;

use Jp7\Laravel\ImgResize as BaseImgResize;

class ImgResize extends BaseImgResize
{
    public static function addTemplate($url, $template)
    {
        /*
        if (static::isExternal($url)) {
            $url = static::downloadExternal($url);
        }
        */
        $uploadPath = self::storageUrl() . '/upload/';
        $cachePath = self::storageUrl() . '/imagecache/' .$template.'/';
        return replace_prefix($uploadPath, $cachePath, $url);
    }

    protected static function getAllTemplates()
    {
        return array_keys(config('imagecache.templates'));
    }
}
