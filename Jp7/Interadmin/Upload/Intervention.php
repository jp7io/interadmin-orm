<?php

class Jp7_Interadmin_Upload_Intervention extends Jp7_Interadmin_Upload_AdapterAbstract
{

    // STORAGE_HOST/imagecache/something/bla/123
    public function imageUrl($path, $template)
    {
        global $config;

        $path = jp7_replace_beginning('upload', 'imagecache/'.$template, $path);

        return $this->url($path);
    }
}
