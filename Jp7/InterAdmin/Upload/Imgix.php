<?php

class Jp7_InterAdmin_Upload_Imgix implements Jp7_InterAdmin_Upload_AdapterInterface
{

    // IMGIX_HOST/upload/bla/123?w=40&h=40
    public function url($path, $template)
    {
        global $config;

        $url = 'http://'.$config->imgix['host'].'/'.$path;

        $params = $config->imgix['templates'][$template];
        
        if ($params) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($params);
        }

        return $url;
    }

}
