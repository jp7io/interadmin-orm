<?php

namespace InterAdmin\Models\Relations;

use Closure;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use InterAdmin\Models\Record;
use InterAdmin\Models\Type;

/**
 * Every record of one type, whose doors build through Type::buildRecord() as ChildRecords' do.
 *
 * @extends HasMany<Record, Type>
 */
class TypeRecords extends HasMany
{
    /**
     * ⚠ Eloquent's own fills a new instance BEFORE `type_id` is set, and no type means no form
     * fields: a MassAssignmentException on an unbound type, and no insert defaults on a bound one.
     */
    public function make(array $attributes = [])
    {
        return $this->type()->buildRecord()->fill($attributes);
    }

    public function create(array $attributes = [])
    {
        return tap($this->make($attributes), fn (Record $record) => $record->save());
    }

    /** The explicit name forces every column it is given. */
    public function forceCreate(array $attributes = [])
    {
        return tap($this->type()->buildRecord($attributes), fn (Record $record) => $record->save());
    }

    /** The lookup keys are FORCED and the values FILLED. */
    public function firstOrNew(array $attributes = [], Closure|array $values = [])
    {
        return $this->where($attributes)->first() ?? $this->type()->buildRecord($attributes)->fill(value($values));
    }

    /** Where firstOrCreate() and updateOrCreate() create. */
    public function createOrFirst(array $attributes = [], Closure|array $values = [])
    {
        try {
            return $this->getQuery()->withSavepointIfNeeded(
                fn () => tap($this->type()->buildRecord($attributes)->fill(value($values)), fn (Record $record) => $record->save())
            );
        } catch (UniqueConstraintViolationException $e) {
            return $this->useWritePdo()->where($attributes)->first() ?? throw $e;
        }
    }

    private function type(): Type
    {
        assert($this->parent instanceof Type);

        return $this->parent;
    }
}
