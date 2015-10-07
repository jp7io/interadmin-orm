<?php

abstract class Jp7_InterAdmin_Upload_AdapterAbstract implements Jp7_InterAdmin_Upload_AdapterInterface
{
    public function url($path)
    {
        global $config;

        return 'http://'.$config->storage['host'].'/'.
            ($config->storage['path'] ? $config->storage['path'].'/' : '') .
            $path;
    }
}