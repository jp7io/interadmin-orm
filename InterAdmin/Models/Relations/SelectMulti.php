<?php

namespace InterAdmin\Models\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * A `select_multi_*` column, which stores its foreign keys as ONE comma-separated string rather
 * than as rows of a pivot -- so no relation Eloquent ships can express it and this is the only
 * bespoke one the port adds.
 *
 * @extends Relation<Model, Model, Collection<int, Model>>
 */
class SelectMulti extends Relation
{
    public function __construct(Builder $query, Model $parent, private string $foreignKey)
    {
        parent::__construct($query, $parent);
    }

    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->whereRelatedIn($this->keysOf($this->parent));
        }
    }

    /** @param array<int, Model> $models */
    public function addEagerConstraints(array $models): void
    {
        $this->whereRelatedIn(array_unique(array_merge(...array_map($this->keysOf(...), $models))));
    }

    /**
     * @param  array<int, Model>  $models
     * @return array<int, Model>
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * ⚠ Filtered out of the RESULT rather than looked up by key, so the list comes back in the
     * QUERY's order -- the related type's own -- which is what the ORM's own match loop does.
     *
     * @param  array<int, Model>  $models
     * @param  Collection<int, Model>  $results
     * @return array<int, Model>
     */
    public function match(array $models, Collection $results, $relation): array
    {
        foreach ($models as $model) {
            $keys = $this->keysOf($model);
            $model->setRelation($relation, $results->filter(
                fn (Model $related) => in_array($related->getKey(), $keys)
            )->values());
        }

        return $models;
    }

    /**
     * ⚠ An empty column costs NO query, as the ORM's own early return does -- the constraint
     * above would answer the same nothing, at the price of a round trip per record.
     * @return Collection<int, Model>
     */
    public function getResults(): Collection
    {
        return $this->keysOf($this->parent) ? $this->query->get() : $this->related->newCollection();
    }

    /**
     * ⚠ No empty-list guard: Eloquent compiles an empty `whereIn` to `0 = 1`, so a column naming
     * nothing already selects nothing, and a guard here would be a second spelling of that.
     * @param array<int, string> $keys
     */
    private function whereRelatedIn(array $keys): void
    {
        $this->query->whereIn($this->related->qualifyColumn($this->related->getKeyName()), $keys);
    }

    /** @return array<int, string> */
    private function keysOf(Model $model): array
    {
        return array_values(array_filter(explode(',', (string) $model->getAttribute($this->foreignKey))));
    }
}
