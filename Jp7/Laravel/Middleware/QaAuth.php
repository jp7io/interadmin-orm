<?php

namespace Jp7\Laravel\Middleware;

use Closure;
use App;

class QaAuth
{
    // Asks password for qa.* or alt.*
    public function handle($request, Closure $next)
    {
        if (!$this->isAuthorized($request)) {
            header('WWW-Authenticate: Basic realm="'.config('app.name').'"');
            header('HTTP/1.0 401 Unauthorized');
            echo '401 Unauthorized';
            exit;
        }
        
        return $next($request);
    }
    
    // We don't want super security
    // We just want to stop curious people and Google from opening the web page
    // While not complicating the development cycle
    protected function isAuthorized($request)
    {
        // QA or Alt environment
        if (!App::environment('staging') && !starts_with($request->getHttpHost(), 'alt.')) {
            return true;
        }
        // Server: Allow our IPs
        $allowedClients = [
            'aws01forge.jp7.com.br',
            'aws11forge.ci.com.br',
            'aws11.ci.com.br',
            'aws01.jp7.com.br',
            'loc01.jp7.com.br',
            'loc02.jp7.com.br',
            'aws01.ci.com.br',
            'aws03.ci.com.br',
        ];
        foreach ($allowedClients as $hostname) {
            if (gethostbyname($hostname) === $request->ip()) {
                return true;
            }
        }
        // Browser: HTTP authentication
        $name = config('app.name');
        if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] === $name && $_SERVER['PHP_AUTH_PW'] === $name) {
            return true;
        }
        return false;
    }
}
