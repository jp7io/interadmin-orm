<?php

namespace InterAdmin\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;

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

    public function make(array $attributes = [])
    {
        return $this->newRecord($attributes);
    }

    public function create(array $attributes = [])
    {
        return tap($this->newRecord($attributes), fn (Record $record) => $record->save());
    }

    public function firstOrNew(array $attributes = [], Closure|array $values = [])
    {
        return $this->where($attributes)->first() ?? $this->newRecord(array_merge($attributes, value($values)));
    }

    /**
     * The ORM's build(), which its create() and firstOrNew() stood on: the type's insert defaults
     * under $attributes, the type read as Record::typeId() reads it. ⚠ Not newModelInstance(): it
     * fill()s a model with no $fillable, which throws, and hydrate() calls it for every query.
     */
    private function newRecord(array $attributes): Record
    {
        $typeId = (int) ($this->model->getAttributes()['type_id'] ?? 0) ?: $this->model::boundTypeId();
        $type = $typeId ? Type::find($typeId) : null;

        if (!$type) {
            return $this->newModelInstance($attributes);
        }

        return $type->buildRecord(array_merge($this->pendingAttributes, $attributes))
            ->setConnection($this->query->getConnection()->getName());
    }
}
