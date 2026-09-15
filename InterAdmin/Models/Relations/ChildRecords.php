<?php

namespace InterAdmin\Models\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InterAdmin\Models\Concerns\BuildsRecords;
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
    use BuildsRecords;

    public function __construct(Builder $query, Model $parent, string $foreignKey, string $localKey, private Type $childType, private ?int $parentTypeId = null)
    {
        parent::__construct($query, $parent, $foreignKey, $localKey);

        $this->query->afterQuery(fn ($records) => $this->applyInverseRelationToCollection($records));
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

    /** ⚠ The parent's columns WIN, as on the ORM and on Eloquent: a posted `parent_id` re-parented the row. */
    protected function newRecord(array $forced): Record
    {
        return $this->childType->buildRecord(array_merge($forced, $this->parentColumns()));
    }

    protected function saveNew(Record $record): Record
    {
        $record->save();
        $this->applyInverseRelationToModel($record);

        return $record;
    }

    /**
     * ⚠ Always, where Laravel's waits for chaperone(): each child read off this parent is handed it,
     * as the ORM's deprecatedFind() did, or every child's getUrl() asks for the parent again.
     */
    protected function applyInverseRelationToModel(Model $model, ?Model $parent = null)
    {
        $parent ??= $this->getParent();

        if ($model instanceof Record && $parent instanceof Record) {
            $model->rememberParent($parent);
        }

        return parent::applyInverseRelationToModel($model, $parent);
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
