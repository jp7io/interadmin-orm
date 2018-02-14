<?php

namespace Jp7\Interadmin;

class RecordClassMap extends BaseClassMap
{
    protected static $classes;
    protected static $instance;

    const CACHE_KEY = 'Interadmin.RecordClassMap';
    const CLASS_ATTRIBUTE = 'class';
}
