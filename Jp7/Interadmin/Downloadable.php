<?php

namespace Jp7\Interadmin;

use ImgResize;
use Exception;
use Storage;

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
        $storageUrl = $config['scheme'].'://'.$config['host'].$config['path'];

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

    private function removeQueryString()
    {
        return parse_url($this->url, PHP_URL_PATH);
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

    public function getContents()
    {
        if ($this->isExternal()) { // Outside S3
            return file_get_contents($this->url);
        } else { // Inside S3
            return Storage::get($this->getFilename());
        }
    }

    /**
     * Copies a file to a disk
     *
     * @param  string $disk One of Laravel disks: local, public or s3
     * @return string Path or URL of the file
     */
    public function copyToDisk($disk, $redownload = false)
    {
        if ($this->isExternal()) { // Outside S3
            $filename = parse_url($this->url, PHP_URL_PATH);
        } else { // Inside S3
            $filename = $this->getFilename();
        }
        if (!Storage::disk($disk)->has($filename) || $redownload) {
            Storage::disk($disk)->put($filename, $this->getContents());
        }
        if ($disk === 'local') {
            return Storage::disk($disk)->getDriver()->getAdapter()->getPathPrefix().$filename;
        } else {
            return Storage::disk($disk)->url($filename);
        }
    }
}
