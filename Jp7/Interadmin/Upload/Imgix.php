<?php

use League\Url\Url;

class Jp7_Interadmin_Upload_Imgix extends Jp7_Interadmin_Upload_AdapterAbstract
{

    // IMGIX_HOST/upload/bla/123?w=40&h=40
    public function imageUrl($path, $template)
    {
        global $config;

        $url = Url::createFromUrl($this->url($path));
    
        $url->setHost($config->imgix['host']);

        $params = $config->imgix['templates'][$template];
        
        if ($params) {
            $url->getQuery()->modify($params);
        }

        return (string) $url;
    }
}
