<?php

namespace Jp7\Laravel;

use Jp7\Interadmin\Downloadable;
use Storage;

/*
Dynamic resize of images: imagecache/<template>/0001.jpg
Download external images to resize them the best way possible
Lazy loading images: data-src="...""
... with fallback for non-js users: <noscript>
Better SEO on the URL: 0001.jpg => some-text-0001.jpg
*/
class ImgResize
{
    private static $lazy = false;
    private static $seo = false;
    private static $minSrcsetWidth = 720;
    
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
    public static function tag($img, $template = null, $options = [])
    {
        if (!is_string($img) && !is_object($img)) {
            throw new \InvalidArgumentException('$img should be a string or use Downloadable trait');
        }
        if (empty($options['title'])) {
            $options['title'] = is_object($img) ? $img->getText() : basename($img);
        }
        $alt = $options['title'];
        
        if (is_null($template) || str_contains($template, '-')) {
            // Normal image with src=""
            // Preppend template to classes for CSS use
            $options['class'] = trim(array_get($options, 'class').' '.$template);
            
            $url = static::url($img, $template, $alt);
            $element = ImgResizeElement::create($url, $alt, $options);
            
            if (static::$lazy) {
                $element->src(static::blankGif())
                    ->data_src($url)
                    ->setLazy(true);
            }
        } else {
            // Image with srcset=""
            $srcset = static::srcset($img, $template);
            $element = ImgResizeElement::create(null, $alt, $options)
                ->srcset($srcset);
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
      
        /*
        if (static::$seo && !static::isExternal($url)) {
            $url = static::seoReplace($url, $title);
        }
        */

        return Cdn::asset($url);
    }

    public static function addTemplate($url, $template)
    {
        /*
        if (static::isExternal($url)) {
            $url = static::downloadExternal($url);
        }
        */
        $uploadPath = self::storageUrl() . '/upload/';
        $cachePath = self::storageUrl() . '/imagecache/' .$template.'/';
        return replace_prefix($uploadPath, $cachePath, $url);
    }
    
    public static function srcset($img, $prefix)
    {
        $all = array_keys(config('imagecache.templates'));
        // If prefix is "wide"
        // Filters all templates starting with "wide-"
        $templates = array_filter($all, function ($x) use ($prefix) {
            return starts_with($x, $prefix . '-');
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
    
    // SEO depends on RewriteModule
    // Should only be applied to local images
    private static function seoReplace($url, $title)
    {
        if (starts_with($url, '/upload')) {
            $basename = basename($url);
            if (preg_match('/^\d{6,8}\./', $basename)) {
                if ($slug = to_slug($title)) {
                    $url = dirname($url).'/'.$slug.'-'.$basename;
                }
            }
        }
        return $url;
    }
   
    private static function isExternal($url)
    {
        $parsed = parse_url($url);
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : '';
        $host = isset($parsed['host']) ? $parsed['host'] : '';

        $baseUrl = $scheme.'://'.$host;

        return ($baseUrl !== self::storageUrl() && $baseUrl !== config('app.url')) ;
    }

    // External images are downloaded locally to resize them
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

    private static function storageUrl()
    {
        return 'http://'.config('interadmin.storage.host');
    }
}
