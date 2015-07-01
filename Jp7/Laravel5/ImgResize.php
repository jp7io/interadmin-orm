<?php

namespace Jp7\Laravel5;

use HtmlObject\Image;

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
    
    public static function tag($img, $template = null, $options = array())
    {
        if ($template) {
            // Preppend template to classes
            $options['class'] = trim(array_get($options, 'class').' '.$template);
        }
        if (empty($options['title'])) {
            $options['title'] = is_object($img) ? $img->getText() : basename($img);
        }
        
        $alt = $options['title'];
        $url = self::url($img, $template, $alt);
        
        if (static::$lazy) {
            $px = Cdn::asset('img/px.gif');
            return self::create($px, $alt, $options)->data_src($url);
        } else {
            return self::create($url, $alt, $options);
        }
    }

    public static function url($url, $template = null, $text = '')
    {
        if (\App::environment('testing')) {
            return '/img/px.gif';
        }
        
        if (is_object($url)) {
            $img = $url;
            $url = $img->getUrl();
        }
        $isExternal = self::isExternal($url);
        
        if (static::$seo && !$isExternal) {
            $url = self::seoReplace($url, $text);
        }
        
        if ($template) {
            if ($isExternal) {
                $url = self::downloadExternal($url);
            }
            $url = str_replace('/upload/', '/imagecache/'.$template.'/', $url);
        }

        return Cdn::asset($url);
    }
    
    // SEO depends on RewriteModule
    // Should only be applied to local images
    private static function seoReplace($url, $text)
    {
        if ($slug = to_slug($text)) {
            $url = dirname($url).'/'.$slug.'-'.basename($url);
        }
        return $url;
    }
    
    private static function isExternal($url)
    {
        return !empty(parse_url($url)['host']);
    }

    // External images are downloaded locally to resize them
    private static function downloadExternal($url)
    {
        $local = to_slug(dirname($url)).'_'.basename($url);
        $dir = public_path('upload/_external');
        
        if (!is_file($dir.'/'.$local)) {
            if ($file = @file_get_contents($url)) {
                file_put_contents($dir.'/'.$local, $file);
            }
        }
        
        return '/upload/_external/'.$local;
    }
    
    // Add <noscript> tags if needed
    public function __toString()
    {
        $noscript = '';
        if (static::$lazy) {
            $attributes = $this->getAttributes();
            $attributes['src'] = $attributes['data-src'];
            unset($attributes['data-src']);

            $noscript = '<noscript>'.Image::create()->setAttributes($attributes).'</noscript>';
        }

        return parent::__toString().$noscript;
    }
}
