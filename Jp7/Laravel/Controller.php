<?php

namespace Jp7\Laravel;

use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected static $current = null;

    protected $layout = 'layouts.master';
    protected $action = '';

    public function __construct()
    {
        static::$current = $this;

        if ($route = \Route::getCurrentRoute()) {
            $this->action = explode('@', $route->getActionName())[1];
        }
    }

    /* Temporary solution - Avoid using this as much as possible */
    public static function getCurrentController()
    {
        return static::$current;
    }
}
