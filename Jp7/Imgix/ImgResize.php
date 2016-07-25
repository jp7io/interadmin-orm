<?php

namespace Jp7\Imgix;

use Jp7\Laravel\ImgResize as BaseImgResize;

class ImgResize extends BaseImgResize
{
    public static function addTemplate($url, $template)
    {
        if (starts_with($url, self::storageUrl())) {
            $url = replace_prefix(self::storageUrl(), self::imgixUrl(), $url);
            
            $params = config('imgix.templates.'.$template);
            
            if ($params) {
                $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($params);
            }
        }
        return $url;
    }
    
    protected static function imgixUrl()
    {
        return 'https://'.config('imgix.host');
    }

    protected static function getAllTemplates()
    {
        $templates = config('imgix.templates');
        if (!$templates) {
            return [];
        }
        return array_keys($templates);
    }
}
