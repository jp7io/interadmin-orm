<?php

namespace Jp7\Interadmin;

trait Downloadable
{
    // For client side use
    public function getUrl()
    {
        $config = config('interadmin.upload');
        // ../../upload => /upload
        return str_replace($config['backend_url'], $config['relative_url'], $this->url);
    }
    
    // Absolute client side URL
    public function getAbsoluteUrl()
    {
        $config = config('interadmin.upload');
        // ../../upload => www.example.com/upload
        return str_replace($config['backend_url'], $config['absolute_url'], $this->url);
    }

    // Server side file name
    public function getFilename()
    {
        if ($this->isExternal()) {
            throw new \Exception('Cant get filename of external image.');
        }
        
        $config = config('interadmin.upload');
        $url = $this->removeQueryString();
        
        return str_replace($config['backend_url'], $config['absolute_path'], $url);
    }
    
    public function removeQueryString()
    {
        $parsed = parse_url($this->url);
        return $parsed['path'];
    }
    
    public function isExternal()
    {
        return !empty(parse_url($this->url)['host']);
    }
    
    /**
     * Returns the extension of this file.
     *
     * @return string Extension, such as 'jpg' or 'gif'.
     */
    public function getExtension()
    {
        return pathinfo($this->removeQueryString(), PATHINFO_EXTENSION);
    }

    public function getSize()
    {
        return human_filesize($this->getFilename());
    }
}
