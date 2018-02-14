<?php

namespace Jp7\Interadmin;

class TypeClassMap extends BaseClassMap
{
    protected static $classes;
    protected static $instance;

    const CACHE_KEY = 'Interadmin.TypeClassMap';
    const CLASS_ATTRIBUTE = 'class_tipo';
}
