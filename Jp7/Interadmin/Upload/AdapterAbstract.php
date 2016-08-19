<?php

abstract class Jp7_Interadmin_Upload_AdapterAbstract implements Jp7_Interadmin_Upload_AdapterInterface
{
    public function url($path)
    {
        global $config;
        
        return $this->getScheme().'://'.$config->storage['host'].'/'.
            ($config->storage['path'] ? $config->storage['path'].'/' : '') .
            $path;
    }

    protected function getScheme()
    {
        return 'http'.(isset($_SERVER['HTTPS']) ? 's' : '');
    }

    protected function setHost($url, $host)
    {
        // Replace host
        return replace_prefix(
            $this->url(''),
            $this->getScheme().'://'.$host.'/',
            $url
        );
    }

    protected function mergeQuery($url, $params)
    {
        $query = http_build_query($params);
        if (!$query) {
            return $url;
        }
        return $url . (str_contains($url, '?') ? '&' : '?') . $query;
    }
}
