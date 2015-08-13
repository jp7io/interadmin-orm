<?php

namespace Jp7\Interadmin;

trait Downloadable
{
    public function isImage()
    {
        return preg_match('/.(jpg|jpeg|png|gif)[#?]?[^?\/#]*$/i', $this->url);
    }

    // Client side URL without hostname and protocol
    public function getPath()
    {
        $backend_path = $this->config('backend_path');
        $path = $this->config('path');

        // ../../upload => /upload
        return replace_prefix($backend_path, $path, $this->url);
    }
    
    // For client side use
    public function getUrl($template = 'original')
    {
        $backend_path = $this->config('backend_path');
        $url = 'http://'.$this->config('host');

        // ../../upload => www.example.com/upload
        $url = replace_prefix($backend_path, $url, $this->url);

        if ($this->isImage()) {
            $url = \Jp7\Laravel\ImgResize::addTemplate($url, $template);
        }

        return $url;
    }

    // Server side file name
    public function getFilename()
    {
        if ($this->isExternal()) {
            throw new \Exception('Cant get filename of external image.');
        }

        $backend_path = $this->config('backend_path');
        $dir = $this->config('dir');
        $url = $this->removeQueryString();
        
        return replace_prefix($backend_path, $dir, $url);
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

    private function config($key)
    {
        return config('interadmin.storage.' . $key);
    }
}
