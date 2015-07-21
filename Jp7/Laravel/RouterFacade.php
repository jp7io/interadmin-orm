<?php

namespace Jp7\Laravel;

use Illuminate\Support\Facades\Facade;

class RouterFacade extends Facade {
    
    protected static function getFacadeAccessor()
    {
        return 'Jp7\Laravel\Router';
    }
    
}