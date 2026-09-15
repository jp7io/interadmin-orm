<?php

namespace InterAdmin\Models\Concerns;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use InterAdmin\Models\Record;

/** A record's doors, once, over each user's newRecord(): the type's insert defaults under every one. */
trait BuildsRecords
{
    /** The type's insert defaults with $forced over them, neither filled nor saved. */
    abstract protected function newRecord(array $forced): Record;

    /** ⚠ Laravel's name FILLS, the form fields alone. Eloquent's own fills before `type_id` is set, when none is fillable. */
    public function make(array $attributes = [])
    {
        return $this->newRecord([])->fill($attributes);
    }

    public function create(array $attributes = [])
    {
        return $this->saveNew($this->make($attributes));
    }

    /** The explicit name forces every column it is given. */
    public function forceCreate(array $attributes = [])
    {
        return $this->saveNew($this->newRecord($attributes));
    }

    /** The lookup keys are FORCED and the values FILLED: Laravel's own fills both, dropping a key no form field names. */
    public function firstOrNew(array $attributes = [], Closure|array $values = [])
    {
        return $this->where($attributes)->first() ?? $this->newRecord($attributes)->fill(value($values));
    }

    /** Where firstOrCreate() and updateOrCreate() create. */
    public function createOrFirst(array $attributes = [], Closure|array $values = [])
    {
        try {
            return $this->withSavepointIfNeeded(
                fn () => $this->saveNew($this->newRecord($attributes)->fill(value($values)))
            );
        } catch (UniqueConstraintViolationException $e) {
            return $this->useWritePdo()->where($attributes)->first() ?? throw $e;
        }
    }

    protected function saveNew(Record $record): Record
    {
        $record->save();

        return $record;
    }
}
