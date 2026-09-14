<?php

namespace InterAdmin\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * The Eloquent builder every record query is, for the lookups the ORM answered its own way.
 * @extends Builder<Record>
 */
final class RecordBuilder extends Builder
{
    /**
     * A non-numeric string finds by `id_slug` where the table has one, as the ORM's find() did:
     * tenant code hands URL slugs straight in (`Pais::find($attr['pais'])`). ⚠ An empty string
     * stays Eloquent's, which matches nothing; as a slug it would match every row that has none.
     */
    public function find($id, $columns = ['*'])
    {
        if (is_string($id) && $id !== '' && !is_numeric($id)
            && in_array('id_slug', $this->model->getColumns(), true)) {
            return $this->where($this->model->qualifyColumn('id_slug'), $id)->first($columns);
        }

        return parent::find($id, $columns);
    }

    /**
     * Soft, as the ORM's Query::delete() stamped `deleted_at`: a relation's or a query's delete()
     * was Eloquent's physical one. forceDelete() stays physical, as does Record::forceDelete().
     */
    public function delete()
    {
        return $this->toBase()->update(['deleted_at' => $this->model->freshTimestampString()]);
    }

    /** Laravel's name keeps Laravel's meaning: the caller's array FILLS, i.e. the type's form fields alone. */
    public function make(array $attributes = [])
    {
        return $this->newRecord([], $attributes);
    }

    public function create(array $attributes = [])
    {
        return tap($this->newRecord([], $attributes), fn (Record $record) => $record->save());
    }

    /** The lookup keys are FORCED and the values FILLED: a key that is no form field still lands. */
    public function firstOrNew(array $attributes = [], Closure|array $values = [])
    {
        return $this->where($attributes)->first() ?? $this->newRecord($attributes, value($values));
    }

    /** Where firstOrCreate() and updateOrCreate() create, and Eloquent's own would fill the keys too. */
    public function createOrFirst(array $attributes = [], Closure|array $values = [])
    {
        try {
            return $this->withSavepointIfNeeded(
                fn () => tap($this->newRecord($attributes, value($values)), fn (Record $record) => $record->save())
            );
        } catch (UniqueConstraintViolationException $e) {
            return $this->useWritePdo()->where($attributes)->first() ?? throw $e;
        }
    }

    /**
     * The ORM's build(), which its create() and firstOrNew() stood on: the type's insert defaults,
     * $forced over them and $filled through its form fields, the type read as Record::typeId() reads
     * it. ⚠ Not newModelInstance(): it fill()s before the type is known, and a model with no type has
     * no $fillable, so any attribute throws; hydrate() calls it for every query.
     */
    private function newRecord(array $forced, array $filled): Record
    {
        $typeId = (int) ($this->model->getAttributes()['type_id'] ?? 0) ?: $this->model::boundTypeId();
        $type = $typeId ? Type::find($typeId) : null;

        if (!$type) {
            return $this->newModelInstance()->forceFill($forced)->fill($filled);
        }

        return $type->buildRecord(array_merge($this->pendingAttributes, $forced))
            ->setConnection($this->model->getConnectionName())
            ->fill($filled);
    }
}
