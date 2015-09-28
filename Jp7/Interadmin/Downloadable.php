<?php

namespace Jp7\Interadmin;

use ImgResize;
use Exception;

trait Downloadable
{
    public function isImage()
    {
        return preg_match('/.(jpg|jpeg|png|gif)[#?]?[^?\/#]*$/i', $this->url);
    }

    // Client side URL without hostname and protocol
    public function getPath()
    {
        $config = config('interadmin.storage');

        // '../..' => ''
        return replace_prefix($config['backend_path'], $config['path'], $this->url);
    }
    
    // For client side use
    public function getUrl($template = 'original')
    {
        $config = config('interadmin.storage');
        $storageUrl = 'http://'.$config['host'].$config['path'];
        
        // '../..' => 'http://www.example.com'
        $url = replace_prefix($config['backend_path'], $storageUrl, $this->url);

        if ($this->isImage()) {
            $url = ImgResize::addTemplate($url, $template);
        }

        return $url;
    }

    // Filename inside Storage
    public function getFilename()
    {
        if ($this->isExternal()) {
            throw new Exception('Cannot get filename of external image.');
        }
        
        $backendPath = config('interadmin.storage.backend_path');
        $url = $this->removeQueryString();
        
        return replace_prefix($backendPath.'/', '', $url);
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
        return Storage::size($this->getFilename());
    }
    
    public function getHumanSize()
    {
        return human_size($this->getSize());
    }
}
