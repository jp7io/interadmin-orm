<?php

namespace Jp7\Interadmin;

use Illuminate\Database\Eloquent\Model;

/**
 * To be used with packages that expect an Eloquent instance. Like Former\Populator.
 */
class EloquentProxy extends Model
{
    private $record;

    public function setRecord(RecordAbstract $record)
    {
        $this->record = $record;
    }

    public function getAttribute($key)
    {
        return $this->record->$key;
    }

    public function setAttribute($key, $value)
    {
        throw new \LogicException('This proxy should be readonly');
    }

    public function save(array $options = [])
    {
        throw new \LogicException('This proxy should be readonly');
    }

    public static function query()
    {
        throw new \LogicException('This proxy should be readonly');
    }
}
