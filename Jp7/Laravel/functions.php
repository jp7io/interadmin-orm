<?php

register_shutdown_function(function () {
    // Avoid CDN cache of assets with errors
    if (error_get_last() && !headers_sent() && isset($_SERVER['SERVER_PROTOCOL'])) {
        header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
        header('Pragma: no-cache'); // HTTP 1.0
        header('Expires: 0'); // Proxies
    }
});

define('CACHE_ENABLED',
    isset($_SERVER['HTTP_HOST']) &&
    $_SERVER['HTTP_HOST'] == CACHE_HOST &&
    strpos($_SERVER['REQUEST_URI'], '/assets') !== 0 &&
    strpos($_SERVER['REQUEST_URI'], '/imagecache') !== 0 &&
    strpos($_SERVER['REQUEST_URI'], '/_debugbar') !== 0
);
// AJAX is not cached, this avoids loading wrong content
define('CACHE_SALT', isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '');

// Check site mobile
function is_mobile()
{
    $userAgent = @$_SERVER['HTTP_USER_AGENT'];
    $pattern1 = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i';
    $pattern2 = '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i';

    return preg_match($pattern1, $userAgent) || preg_match($pattern2, substr($userAgent, 0, 4));
}

function human_filesize($file, $decimals = 2)
{
    $bytes = @filesize($file);
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$size[$factor];
}

function to_slug($string, $separator = '-')
{
    $string = str_replace('/', '-', $string);
    $string = str_replace('®', '', $string);
    $string = str_replace('&', 'e', $string);

    return Str::slug($string, $separator);
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

function km($object, $search = '.*')
{
    $methods = get_class_methods($object);
    $methods = array_filter($methods, function ($a) use ($search) {
        return preg_match('/'.$search.'/i', $a);
    });

    kd(['methods' => $methods, 'object' => $object], KRUMO_EXPAND_ALL);
}

// INTERADMIN COMPATIBILITY FUNCTIONS
function jp7_debug($msg)
{
    throw new Exception($msg);
}

// Laravel 5 functions
function collect($arr)
{
    return new \Jp7\Interadmin\Collection($arr);
}

// OVERRIDE LARAVEL FUNCTIONS
function snake_case($value, $delimiter = '_')
{
    if (ctype_lower($value)) {
        return $value;
    }

    return strtolower(preg_replace('/(.)(?=[A-Z])/', '$1'.$delimiter, $value));
}
