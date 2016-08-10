<?php

namespace Jp7\Laravel;

use Illuminate\Routing\Controller as BaseController;
use Jp7\Laravel\Controller\RecordTrait;
use Jp7\Laravel\Controller\DynamicViewTrait;

class Controller extends BaseController
{
    use RecordTrait, DynamicViewTrait;

    protected static $current = null;

    protected $action = '';

    public function __construct()
    {
        static::$current = $this;

        if ($route = \Route::getCurrentRoute()) {
            $this->action = explode('@', $route->getActionName())[1];
        }

        $this->constructDynamicViewTrait();
        $this->constructRecordTrait();
    }

    /* Temporary solution - Avoid using this as much as possible */
    public static function getCurrentController()
    {
        return static::$current;
    }
}
