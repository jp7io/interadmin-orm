<?php

abstract class Jp7_Interadmin_Upload_AdapterAbstract implements Jp7_Interadmin_Upload_AdapterInterface
{
    public function url($path)
    {
        global $config;
        
        $protocol = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '');
        return $protocol.'://'.$config->storage['host'].'/'.
            ($config->storage['path'] ? $config->storage['path'].'/' : '') .
            $path;
    }

    protected function setHost($url, $host)
    {
        $parts = parse_url($url);
        // Replace host
        return replace_prefix(
            $parts['scheme'].'://'.$parts['host'],
            $parts['scheme'].'://'.$host,
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
