<?php
/*
LARAVEL 4
*/
namespace Jp7\Laravel;

use HtmlObject\Image;

class ImgResize extends Image
{
    private static $lazy = false;

    public static function getLazy()
    {
        return static::$lazy;
    }

    public static function setLazy($lazy)
    {
        static::$lazy = (bool) $lazy;
    }

    public static function tag($img, $template = null, $options = array())
    {
        if (!$img) {
            return;
        }

        $url = self::url($img, $template);
        if ($template) {
            $options['class'] = trim(array_get($options, 'class').' '.$template);
        }
        if (empty($options['title'])) {
            $options['title'] = is_object($img) ? $img->getText() : '';
            $options['title'] = $options['title'] ?: basename($url);
        }
        $alt = $options['title'];

        if (static::$lazy) {
            return self::create(Cdn::asset('img/px.gif'), $alt, $options)->data_src($url);
        } else {
            return self::create($url, $alt, $options);
        }
    }

    public static function url($url, $template = null)
    {
        if (\App::environment('testing')) {
            return '/img/px.gif';
        }

        if (is_object($url)) {
            $url = $url->getUrl();
        }
        if ($template) {
            $url = self::resolveExternal($url);
            $url = str_replace('/assets/', '/imagecache/'.$template.'/', $url);
        }

        return Cdn::asset($url);
    }

    // External images are downloaded locally to resize them
    private static function resolveExternal($url)
    {
        if (empty(parse_url($url)['host'])) {
            return $url;
        }

        $local = to_slug(dirname($url)).'_'.basename($url);
        $dir = public_path('upload/_external');

        if (!is_file($dir.'/'.$local) && !\App::environment('testing')) {
            if ($file = @file_get_contents($url)) {
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                file_put_contents($dir.'/'.$local, $file);
            }
        }

        return '/assets/_external/'.$local;
    }

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
