<?php

namespace Jp7\Laravel;

use Illuminate\Http\Request;
use Cookie;
use Artisan;

trait InteradminControllerTrait
{
    // Redirect to remote interadmin
    public function index()
    {
        return redirect('http://'.config('interadmin.host').'/'.config('app.name'));
    }
    
    public function show(Request $request, $action)
    {
        if ($action === 'session') {
            return $this->session($request);
        }
        if ($action === 'log-update') {
            return $this->logUpdate();
        }
        return abort(404);
    }
    // Set cookie to flag that user MIGHT have access to interadmin
    // Access should be validated elsewhere
    public function session(Request $request)
    {
        $s_cookie = ['user' => $request->user];
        // You can get it later with => Cookie::get('interadmin')
        $cookie = Cookie::forever('interadmin', $s_cookie);
        return response(date('c'))
            ->withCookie($cookie)
            ->withHeaders([
                'Access-Control-Allow-Origin' => '*'
            ]);
    }
    // Used to invalidate cache
    public function logUpdate()
    {
        Artisan::call('httpcache:clear');
        return date('c');
    }
}
