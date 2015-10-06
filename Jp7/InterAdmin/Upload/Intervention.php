<?php

class Jp7_InterAdmin_Upload_Intervention implements Jp7_InterAdmin_Upload_AdapterInterface
{

    // STORAGE_HOST/imagecache/something/bla/123
    public function url($path, $template)
    {
        global $config;

        $path = jp7_replace_beginning('upload', 'imagecache/'.$template, $path);

        $url = 'http://'.$config->storage['host'].'/'.$path;
                
        return $url;
    }

}
