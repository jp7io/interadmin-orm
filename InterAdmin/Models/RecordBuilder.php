<?php

namespace InterAdmin\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

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
     * ⚠ The KEY without a model per value: an incrementing key counts as a cast, so Eloquent's
     * pluck('id') hydrated every row to read it back as an int -- 32,333 models on one page.
     */
    public function pluck($column, $key = null)
    {
        $name = is_string($column) ? Str::after($column, $this->model->getTable().'.') : null;

        if ($name !== $this->model->getKeyName() || $this->model->getKeyType() !== 'int'
            || $this->model->hasAnyGetMutator($name)) {
            return parent::pluck($column, $key);
        }

        return $this->applyAfterQueryCallbacks(
            $this->toBase()->pluck($column, $key)->map(fn ($id) => $id === null ? null : (int) $id)
        );
    }

    /**
     * This query's keys as a subquery for whereIn(): the key column ALONE. select() would put the
     * identity columns in front of it, and MySQL refuses an IN subquery of several columns.
     */
    public function keySubquery(): \Illuminate\Database\Query\Builder
    {
        $query = $this->toBase();
        $query->columns = [$this->model->qualifyColumn($this->model->getKeyName())];

        return $query;
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
