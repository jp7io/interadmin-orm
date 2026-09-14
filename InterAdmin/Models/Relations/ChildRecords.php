<?php

namespace InterAdmin\Models\Relations;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use InterAdmin\Models\Record;
use InterAdmin\Models\Type;

/**
 * A declared child type's records under one parent record.
 * ⚠ A child row names its parent by BOTH `parent_id` and `parent_type_id`, where HasMany writes
 * only the key it matches on.
 *
 * @extends HasMany<Record, Record>
 */
class ChildRecords extends HasMany
{
    public function __construct(Builder $query, Model $parent, string $foreignKey, string $localKey, private Type $childType, private ?int $parentTypeId = null)
    {
        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

    public function addConstraints()
    {
        parent::addConstraints();

        if (static::$constraints) {
            $this->query->where($this->parentTypeColumn(), $this->parentTypeId);
        }
    }

    /** ⚠ Off the parents, never the blank Eloquent builds this on: its type is the bound class's. */
    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $types = array_unique(array_map(fn (Model $model) => (int) $model->getAttribute('type_id'), $models));
        $this->query->whereIn($this->parentTypeColumn(), array_values($types));
    }

    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query = parent::getRelationExistenceQuery($query, $parentQuery, $columns);

        return $query->where($query->getModel()->getTable().'.parent_type_id', $this->parentTypeId);
    }

    /** The ORM's `$record->child()->build()`: the child's insert defaults, then its form fields filled. */
    public function make(array $attributes = [])
    {
        return $this->build([])->fill($attributes);
    }

    /**
     * The ORM's `create()`, which is build() then save(). ⚠ Eloquent's own bypasses make(): it
     * fill()s a model with no $fillable, which throws, and would skip the insert defaults.
     */
    public function create(array $attributes = [])
    {
        return tap($this->make($attributes), function (Record $record) {
            $record->save();
            $this->applyInverseRelationToModel($record);
        });
    }

    /** The explicit name forces what it is given; the parent's columns still win. */
    public function forceCreate(array $attributes = [])
    {
        return tap($this->build($attributes), function (Record $record) {
            $record->save();
            $this->applyInverseRelationToModel($record);
        });
    }

    /** ⚠ Eloquent's own bypasses make(), filling the keys too: here they are FORCED, the values filled. */
    public function firstOrNew(array $attributes = [], Closure|array $values = [])
    {
        return $this->where($attributes)->first() ?? $this->build($attributes)->fill(value($values));
    }

    /** Where firstOrCreate() and updateOrCreate() create. */
    public function createOrFirst(array $attributes = [], Closure|array $values = [])
    {
        try {
            return $this->getQuery()->withSavepointIfNeeded(fn () => tap($this->build($attributes)->fill(value($values)), function (Record $record) {
                $record->save();
                $this->applyInverseRelationToModel($record);
            }));
        } catch (UniqueConstraintViolationException $e) {
            return $this->useWritePdo()->where($attributes)->first() ?? throw $e;
        }
    }

    /** ⚠ The parent's columns WIN, as on the ORM and on Eloquent: a posted `parent_id` re-parented the row. */
    private function build(array $forced): Record
    {
        return $this->childType->buildRecord(array_merge($forced, $this->parentColumns()));
    }

    protected function setForeignAttributesForCreate(Model $model)
    {
        parent::setForeignAttributesForCreate($model);

        $model->setAttribute('parent_type_id', $this->parent->getAttribute('type_id'));
    }

    private function parentTypeColumn(): string
    {
        return $this->related->getTable().'.parent_type_id';
    }

    /** @return array{parent_id: mixed, parent_type_id: mixed} */
    private function parentColumns(): array
    {
        return ['parent_id' => $this->getParentKey(), 'parent_type_id' => $this->parent->getAttribute('type_id')];
    }
}
