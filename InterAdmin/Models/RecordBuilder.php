<?php

namespace InterAdmin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use InterAdmin\Models\Concerns\BuildsRecords;

/**
 * The Eloquent builder every record query is, for the lookups the ORM answered its own way.
 * @extends Builder<Record>
 */
final class RecordBuilder extends Builder
{
    use BuildsRecords;

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
     * The builder's own hydrate(). Eloquent's asks the MODEL, which has none: that lands in
     * Record::__call(), a relationship lookup and a second builder, once per query.
     */
    public function getModels($columns = ['*'])
    {
        return $this->hydrate($this->query->get($columns)->all())->all();
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
            $this->toBase()->pluck($column, $key)->map(fn ($id): ?int => $id === null ? null : (int) $id)
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

    /**
     * The ORM's build(): the type's insert defaults with $forced over them, the type read as
     * Record::typeId() reads it. ⚠ Not newModelInstance(): it fill()s before the type is known, and a
     * model with no type has no $fillable, so any attribute throws; hydrate() calls it for every query.
     */
    protected function newRecord(array $forced): Record
    {
        $typeId = (int) ($this->model->getAttributes()['type_id'] ?? 0) ?: $this->model::boundTypeId();
        $type = $typeId ? Type::find($typeId) : null;

        if (!$type) {
            return $this->newModelInstance()->forceFill($forced);
        }

        return $type->buildRecord(array_merge($this->pendingAttributes, $forced))
            ->setConnection($this->model->getConnectionName());
    }
}
