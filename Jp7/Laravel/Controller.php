<?php

namespace Jp7\Laravel;

class Controller extends \Illuminate\Routing\Controller
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
