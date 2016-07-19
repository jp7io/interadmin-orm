<?php

use League\Uri\Schemes\Http as Uri;

class Jp7_Interadmin_Upload_Legacy extends Jp7_Interadmin_Upload_AdapterAbstract
{
    public static $templates = [
        'thumb_interadmin' => '40x40'
    ];

    // $config->url/upload/bla/123?size=40x40
    public function imageUrl($path, $template)
    {
        $url = Uri::createFromString($this->url($path));

        $template = self::$templates[$template];

        $url->getQuery()->modify(['size' => $template]);
        
        return (string) $url;
    }
}

