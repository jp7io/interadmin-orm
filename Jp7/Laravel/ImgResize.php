<?php

namespace Jp7\Laravel;

use HtmlObject\Image;
use Jp7\Interadmin\Downloadable;

/*
Dynamic resize of images: imagecache/<template>/0001.jpg
Download external images to resize them the best way possible
Lazy loading images: data-src="...""
... with fallback for non-js users: <noscript>
Better SEO on the URL: 0001.jpg => some-text-0001.jpg
*/
class ImgResize extends Image
{
    private static $lazy = false;
    private static $seo = false;
    
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
    
    /**
     * Generates image tag. Can create lazy loading images with "data-src".
     *
     * @param string|Downloadable   $img        URL or Downloadable object
     * @param string|array          $templates  Multiple templates become "srcset"
     * @param array                 $options    HTML options such as title, or class.
     */
    public static function tag($img, $templates = [], $options = [])
    {
        if (!is_string($img) && !is_object($img)) {
            throw new \InvalidArgumentException('$img should be a string or use Downloadable trait');
        }
        
        $templates = (array) $templates;
        $mainTemplate = $templates ? $templates[0] : null;
        
        if (count($templates) === 1) {
            // Preppend template to classes for CSS use
            $options['class'] = trim(array_get($options, 'class').' '.$mainTemplate);
        }
        if (empty($options['title'])) {
            $options['title'] = is_object($img) ? $img->getText() : basename($img);
        }
        
        $alt = $options['title'];
        $url = static::url($img, $mainTemplate, $alt);
        
        $obj = static::create($url, $alt, $options);
        
        if (static::$lazy) {
            $obj->src(static::transparentGif())
                ->data_src($url);
        }
        if (count($templates) > 1) {
            // TODO
        }
        
        return $obj;
    }

    public static function url($url, $template = null, $title = '')
    {
        // local test:
        // $url = '/upload/mediabox/00202271.jpg';
        
        if (is_object($url)) {
            $img = $url;
            $url = $img->getUrl();
        }
        $isExternal = static::isExternal($url);
        
        if (static::$seo && !$isExternal) {
            $url = static::seoReplace($url, $title);
        }
        
        if ($template) {
            if ($isExternal) {
                $url = static::downloadExternal($url);
            }
            $url = str_replace('/upload/', '/imagecache/'.$template.'/', $url);
        }

        return Cdn::asset($url);
    }
    
    public static function bg($url, $template = null, $title = '')
    {
        return 'background-image: url(\'' . static::url($url, $template, $title) . '\')';
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
    
    public static function transparentGif()
    {
        return 'data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=';
    }
    
    private static function isExternal($url)
    {
        return !empty(parse_url($url)['host']);
    }

    // External images are downloaded locally to resize them
    private static function downloadExternal($url)
    {
        $local = to_slug(dirname($url)).'_'.to_slug(basename($url));
        $dir = public_path('upload/_external');
        
        if (!is_file($dir.'/'.$local)) {
            if ($file = @file_get_contents($url)) {
                file_put_contents($dir.'/'.$local, $file);
            }
        }
        
        return '/upload/_external/'.$local;
    }
    
    // Add <noscript> tags if needed
    public function render()
    {
        $noscript = '';
        
        if (static::$lazy) {
            $attributes = $this->getAttributes();
            $attributes['src'] = $attributes['data-src'];
            unset($attributes['data-src']);
            unset($attributes['srcset']);
            
            $noscript = '<noscript>'.Image::create()->setAttributes($attributes).'</noscript>';
        }
        
        return parent::render().$noscript;
    }
}
