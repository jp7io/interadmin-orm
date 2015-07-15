<?php

namespace Jp7\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class HttpCacheExtension extends HttpCache
{
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if ($this->matchesBlacklist($request)) {
            return $this->pass($request, $catch);
        }
        
        if (config('httpcache.invalidate')) {
            return $this->invalidate($request, $catch);
        }
        
        if (!config('httpcache.enabled')) {
            $response = $this->pass($request, $catch);
        } else {
            $response = parent::handle($request, $type, $catch);
        }
        
        if ($response instanceof \Illuminate\Http\Response) {
            if (str_contains($response->getContent(), 'method="POST"')) {
                throw new \Exception('Forms with CSRF token should not be cached. Add it to httpcache.blacklist.');
            }
        }
        
        return $response;
    }
    
    public function matchesBlacklist(Request $request)
    {
        $blacklist = config('httpcache.blacklist');
        $pattern = '/^(' . addcslashes(implode('|', $blacklist), '/') . ').*/';
        return preg_match($pattern, $request->path());
    }
}


