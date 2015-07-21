<?php

namespace Jp7\Laravel\Middleware;

use Closure;

class QaAuth
{
    // Asks password for qa.* or alt.*
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        if (starts_with($host, 'qa.') || starts_with($host, 'alt.')) {
            $name = config('app.name');
            if (empty($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $name || $_SERVER['PHP_AUTH_PW'] != $name) {
                header('WWW-Authenticate: Basic realm="'.$name.'"');
                header('HTTP/1.0 401 Unauthorized');
                echo '401 Unauthorized';
                exit;
            }
        }
        
        return $next($request);
    }
}
