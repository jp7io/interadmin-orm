<?php
namespace Jp7\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\Store as BaseStore;
use Symfony\Component\HttpFoundation\Request;

class Store extends BaseStore
{
    // Separate AJAX cache from normal cache
    protected function generateCacheKey(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            return 'md'.hash('sha256', $request->getUri() . '#' . $request->isXmlHttpRequest());
        }
    }
}
