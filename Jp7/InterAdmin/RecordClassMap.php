<?php

namespace Jp7\InterAdmin;

use Jp7\InterAdmin\Schema\RecordClassMap as SchemaRecordClassMap;

/** The ORM's name for jp7io/classes' record class map, which it hands out: one map, never a second. */
class RecordClassMap
{
    public static function getInstance(): SchemaRecordClassMap
    {
        return SchemaRecordClassMap::getInstance();
    }
}
