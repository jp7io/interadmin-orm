<?php

class Jp7_Interadmin_Upload_Imgix extends Jp7_Interadmin_Upload_AdapterAbstract
{

    // IMGIX_HOST/upload/bla/123?w=40&h=40
    public function imageUrl($path, $template)
    {
        global $config;

        $url = $this->url($path);
        
        // Replace host
        $url = $this->setHost($url, $config->imgix['host']);
        
        $params = $config->imgix['templates'][$template];
        if ($params) {
            $url = $this->mergeQuery($url, $params);
        }
        return (string) $url;
    }
}
