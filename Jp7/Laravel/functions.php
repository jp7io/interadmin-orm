<?php

if (!function_exists('interadmin_data')) {
    /**
     * Converts 1024 to 1kB
     *
     * @param int $bytes
     * @param int $decimals
     * @return string   Human readable size
     */
    function human_size($bytes, $decimals = 2)
    {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$size[$factor];
    }
    /**
     * Converts "1 hour" to 3600
     *
     * @param string
     * @return int
     */
    function time_to_int($time)
    {
        return strtotime($time) - time();
    }

    function to_slug($string, $separator = '-')
    {
        $string = str_replace('/', '-', $string);
        $string = str_replace('®', '', $string);
        $string = str_replace('&', 'e', $string);

        return str_slug($string, $separator);
    }

    function interadmin_data($record)
    {
        if ($record instanceof InterAdmin) {
            echo ' data-ia="'.$record->id.':'.$record->id_tipo.'"';
        }
    }

    function error_controller($action)
    {
        $request = Request::create('/error/'.$action, 'GET', array());

        return Route::dispatch($request);
    }

    function link_open($url, $attributes = array())
    {
        return substr(link_to($url, '', $attributes), 0, -4);
    }

    function link_close()
    {
        return '</a>';
    }

    function img_tag($img, $template = null, $options = array())
    {
        return ImgResize::tag($img, $template, $options);
    }

    function _try($object)
    {
        return $object ?: new \Jp7\NullObject();
    }

    function memoize(Closure $closure)
    {
        static $memoized = [];

        list(, $caller) = debug_backtrace(false, 2);

        $key = $caller['class'].':'.$caller['function'];

        foreach ($caller['args'] as $arg) {
            $key .= ",\0".(is_array($arg) ? serialize($arg) : (string) $arg);
        }

        $cache = &$memoized[$key];

        if (!isset($cache)) {
            $cache = call_user_func_array($closure, $caller['args']);
        }

        return $cache;
    }

    function dm($object, $search = '.*')
    {
        $methods = [];
        if (is_object($object)) {
            $methods = get_class_methods($object);
            $methods = array_filter($methods, function ($a) use ($search) {
                return preg_match('/'.$search.'/i', $a);
            });
        }
        
        dd(compact('methods', 'object'));
    }

    // INTERADMIN COMPATIBILITY FUNCTIONS
    function jp7_debug($msg)
    {
        throw new Exception($msg);
    }

    // Laravel 5 functions
    function jp7_collect($arr = null)
    {
        return new \Jp7\Interadmin\Collection($arr);
    }
}
