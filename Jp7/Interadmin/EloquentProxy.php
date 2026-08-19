<?php

namespace Jp7\Interadmin;

use Illuminate\Database\Eloquent\Model;

/**
 * To be used with packages that expect an Eloquent instance. Like Former\Populator.
 */
class EloquentProxy extends Model
{
    private ?\Jp7\Interadmin\RecordAbstract $record = null;

    public function setRecord(RecordAbstract $record): void
    {
        $this->record = $record;
    }

    public function getAttribute($key)
    {
        return $this->record->$key;
    }

    public function setAttribute($key, $value): never
    {
        throw new \LogicException('This proxy should be readonly');
    }

    public function save(array $options = []): never
    {
        throw new \LogicException('This proxy should be readonly');
    }

    public static function query(): never
    {
        throw new \LogicException('This proxy should be readonly');
    }
}
