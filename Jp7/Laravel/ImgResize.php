<?php

namespace Jp7\Laravel;

use Jp7\Interadmin\Downloadable;
//use Storage;
use InvalidArgumentException;

/*
Dynamic resize of images: imagecache/<template>/0001.jpg
Download external images to resize them the best way possible
Lazy loading images: data-src="...""
... with fallback for non-js users: <noscript>
Better SEO on the URL: 0001.jpg => some-text-0001.jpg
*/
class ImgResize
{
    protected static $lazy = false;
    protected static $seo = false;
    protected static $minSrcsetWidth = 720;
    
    public static function getLazy()
    {
        return static::$lazy;
    }

    public static function setLazy($status)
    {
        static::$lazy = (bool) $status;
    }
    
    public static function getSeo()
    {
        return static::$seo;
    }

    public static function setSeo($status)
    {
        static::$seo = (bool) $status;
    }
    
    public static function getMinSrcsetWidth()
    {
        return static::$minSrcsetWidth;
    }

    public static function setMinSrcsetWidth($minSrcsetWidth)
    {
        static::$minSrcsetWidth = $minSrcsetWidth;
    }
    
    /**
     * Generates image tag. Can create lazy loading images with "data-src".
     *
     * @param string|Downloadable   $img        URL or Downloadable object
     * @param string|array          $template   Name of the template, or prefix for "srcset"
     * @param array                 $options    HTML options such as title, or class.
     */
    public static function tag($img, $template = 'original', $options = [])
    {
        if (!is_string($img) && !is_object($img)) {
            throw new InvalidArgumentException('$img should be a string or use Downloadable trait');
        }
        if (empty($options['title'])) {
            $options['title'] = is_object($img) ? $img->getText() : basename($img);
        }
        $alt = $options['title'];
        
        return static::makeElement($img, $template, $alt, $options);
    }
    
    protected static function makeElement($img, $template, $alt, $options)
    {
        if (ends_with($template, '-')) {
            // Image with srcset=""
            $srcset = static::srcset($img, $template);
            $element = ImgResizeElement::create(null, $alt, $options)
                ->srcset($srcset);
        } else {
            // Normal image with src=""
            // Prepend template to classes for CSS use
            $options['class'] = trim(array_get($options, 'class').' '.$template);
            
            $url = static::url($img, $template, $alt);
            $element = ImgResizeElement::create($url, $alt, $options);
            
            if (static::$lazy) {
                $element->src(static::blankGif())
                    ->data_src($url)
                    ->setLazy(true);
            }
        }
        
        return $element;
    }

    public static function url($url, $template = 'original', $title = '')
    {
        // local test: $url = '/upload/mediabox/00202271.jpg';
        if (is_object($url)) {
            $img = $url;
            $url = $img->getUrl($template);
        }
      
        if (static::$seo) {
            $url = static::seoReplace($url, $title);
        }
        
        return Cdn::asset($url);
    }
    
    protected static function storageUrl()
    {
        return 'http://'.config('interadmin.storage.host');
    }
    
    public static function srcset($img, $prefix)
    {
        if (!ends_with($prefix, '-')) {
            throw new InvalidArgumentException('Prefix for srcset must end with dash (Eg: small- or large-)');
        }
        $all = static::getAllTemplates();
        // If prefix is "wide-"
        // Filters all templates starting with "wide-"
        $templates = array_filter($all, function ($x) use ($prefix) {
            return starts_with($x, $prefix);
        });
        
        $srcs = [];
        foreach ($templates as $template) {
            $parts = explode('-', $template);
            $width = end($parts);
            
            if ($width >= static::$minSrcsetWidth) {
                $srcs[] = static::url($img, $template) . " ${width}w";
            }
        }
        
        return implode(', ', $srcs);
    }
    
    public static function bg($url, $template = null, $title = '')
    {
        return 'background-image: url(\'' . static::url($url, $template, $title) . '\')';
    }
    
    public static function blankGif()
    {
        return 'data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=';
    }
    
    // SEO
    // Should only be applied to local images
    private static function seoReplace($url, $title)
    {
        if (!$title || static::isExternal($url)) {
            return $url;
        }
        return $url.(str_contains($url, '?') ? '&' : '?').
            'title='.to_slug($title);
    }
    
    protected static function isExternal($url)
    {
        return parse_url($url, PHP_URL_HOST) != parse_url(self::storageUrl(), PHP_URL_HOST);
    }
    
    // External images are downloaded locally to resize them
    /*
    private static function downloadExternal($url)
    {
        $local = static::urlToFilename($url);
        $filePath = '/upload/_external/' . $local;
        
        if (!Storage::has($filePath)) {
            if ($file = @file_get_contents($url)) {
                Storage::put($filePath, $file);
            }
        }
        
        return self::storageUrl() . $filePath;
    }

    private static function urlToFilename($url)
    {
        $parsed = parse_url($url);

        $filename = [];
        $filename[] = $parsed['host'];
        $filename[] = dirname($parsed['path']);
        $filename[] = basename($parsed['path']);

        $filename = array_map(function($x) {
            return preg_replace('/[^a-z0-9_.-]/ui', '', $x);
        }, $filename);

        return implode('_', $filename);
    }
    */
}
