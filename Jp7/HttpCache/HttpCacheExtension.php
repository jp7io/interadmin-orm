<?php

namespace Jp7\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class HttpCacheExtension extends HttpCache
{
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (!config('httpcache.enabled') || $this->matchesBlacklist($request)) {
            return $this->pass($request, $catch);
        }
        
        if (config('httpcache.invalidate')) {
            return $this->invalidate($request, $catch);
        }
        
        return parent::handle($request, $type, $catch);
    }
    
    public function matchesBlacklist(Request $request)
    {
        $blacklist = config('httpcache.blacklist');
        $pattern = '/^(' . addcslashes(implode('|', $blacklist), '/') . ').*/';
        return preg_match($pattern, $request->path());
    }
}


