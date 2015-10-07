<?php

use League\Url\Url;

class Jp7_InterAdmin_Upload_Legacy extends Jp7_InterAdmin_Upload_AdapterAbstract
{
    public static $templates = [
        'thumb_interadmin' => '40x40'
    ];

    // $config->url/upload/bla/123?size=40x40
    public function imageUrl($path, $template)
    {
        $url = Url::createFromUrl($this->url($path));

        $template = self::$templates[$template];

        $url->getQuery()->modify(['size' => $template]);
        
        return (string) $url;
    }
}

