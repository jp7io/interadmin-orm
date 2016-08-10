<?php

namespace Jp7\Laravel;

trait RedirectModelTrait
{
    public static function match($url)
    {
        $parts = parse_url($url) + ['path' => '', 'query' => ''];
        $path = substr($parts['path'], 1); // remove starting /
        $pathWithQuery = $path.($parts['query'] ? '?'.$parts['query'] : '');
        // Search for whole URI with query string
        $redirect = self::search($pathWithQuery)->first();
        if (!$redirect) {
            // Search only PATH
            $redirect = self::search($path)->first();
        }
        if ($redirect) {
            return preg_replace('#^'.$redirect->url.'$#', $redirect->destino, $pathWithQuery);
        }
    }

    public function scopeSearch($query, $path)
    {
        $quoted = \DB::getPdo()->quote($path);
        return $query->whereRaw($quoted." REGEXP CONCAT('^', url, '$')");
    }
}
