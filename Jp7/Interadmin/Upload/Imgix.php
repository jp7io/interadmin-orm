<?php

use League\Uri\Schemes\Http as Uri;

class Jp7_Interadmin_Upload_Imgix extends Jp7_Interadmin_Upload_AdapterAbstract
{

    // IMGIX_HOST/upload/bla/123?w=40&h=40
    public function imageUrl($path, $template)
    {
        global $config;

        $uri = Uri::createFromString($this->url($path));
    
        $uri->setHost($config->imgix['host']);

        $params = $config->imgix['templates'][$template];
        
        if ($params) {
            $uri->getQuery()->modify($params);
        }

        return (string) $uri;
    }
}
