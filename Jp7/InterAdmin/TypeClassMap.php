<?php

namespace Jp7\InterAdmin;

class TypeClassMap extends BaseClassMap
{
    protected static $instance;

    // Keeps the pre-recase spelling on purpose: this is a live cache key, not a class
    // reference, and changing it orphans every tenant's entry rather than moving it.
    const CACHE_KEY = 'Interadmin.TypeClassMap';
    const CLASS_ATTRIBUTE = 'class_tipo';
}
