<?php
/* Laravel 4 */
namespace Jp7\Flysystem;

use Illuminate\Support\Facades\Facade;

class FlysystemFacade extends Facade {

    public static function getFacadeRoot() {
        return new FlysystemFactory;
    }

}
