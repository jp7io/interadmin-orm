<?php

class Jp7_Interadmin_Upload_Legacy extends Jp7_Interadmin_Upload_AdapterAbstract
{
    public static $templates = [
        'thumb_interadmin' => '40x40'
    ];

    // $config->url/upload/bla/123?size=40x40
    public function imageUrl($path, $template)
    {
        $url = $this->url($path);

        $template = self::$templates[$template];

        $url = $this->mergeQuery($url, ['size' => $template]);

        return (string) $url;
    }
}
