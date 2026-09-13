<?php

namespace Jp7\InterAdmin;

use Jp7\InterAdmin\Schema\TypeClassMap as SchemaTypeClassMap;

/** The ORM's name for jp7io/classes' type class map, which it hands out: one map, never a second. */
class TypeClassMap
{
    public static function getInstance(): SchemaTypeClassMap
    {
        return SchemaTypeClassMap::getInstance();
    }
}
