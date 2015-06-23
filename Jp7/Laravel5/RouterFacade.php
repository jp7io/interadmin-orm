<?php

namespace Jp7\Laravel5;

use Illuminate\Support\Facades\Facade;

class RouterFacade extends Facade {
    
    protected static function getFacadeAccessor()
    {
        return 'Jp7\Laravel5\Router';
    }
    
}