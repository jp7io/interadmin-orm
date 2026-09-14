<?php

namespace InterAdmin\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Jp7\InterAdmin\Schema\FieldDefinitions;

/** A record query's alias layer on Eloquent's builder. ⚠ Every builder method taking a column as
 *  parameter 0 is overridden below; one left out compiles the alias as a column name, a 42S22. */
final class RecordQuery extends Builder
{
    /**
     * ⚠ Kept on every partial SELECT, as Type::deprecatedFind() keeps them: a hydrated record
     * with no `type_id` cannot read its own alias map, so `select('<alias>')` would answer null
     * for the very name it asks by. Intersected with the table, `id_slug` being optional.
     */
    private const IDENTITY = ['id', 'type_id', 'id_slug'];

    private ?Record $record = null;

    private bool $typeOrdered = false;

    /** The record whose alias map and columns this query resolves against. */
    public function forRecord(Record $record): static
    {
        $this->record = $record;

        return $this;
    }

    /**
     * ⚠ Carries the record, and a SUBQUERY is what reaches this: `whereIn('id', fn ($q) => ...)`
     * loses the translation without it while the rest of the clause keeps it. A nested closure
     * does not come through here -- Eloquent hands that one a builder of the model's own.
     */
    public function newQuery()
    {
        $query = new static($this->connection, $this->grammar, $this->processor);

        return $this->record ? $query->forRecord($this->record) : $query;
    }

    public function select($columns = ['*'])
    {
        return parent::select($this->selectList(is_array($columns) ? $columns : func_get_args()));
    }

    /**
     * The type's own ORDER BY, after the caller's, for a class that opts in: tenant code written
     * against the ORM leans on it at every first() and list. An aggregate never gets here with
     * `aggregate` unset, which is the ORM's own exemption, and exists() never gets here at all.
     */
    protected function runSelect()
    {
        $typeId = $this->record ? ((int) $this->record->type_id ?: $this->record::boundTypeId()) : null;

        // ⚠ Nor on a GROUP BY past the key: ONLY_FULL_GROUP_BY refuses an ORDER BY outside the group
        // and accepts one the primary key determines, the `GROUP BY id` the ORM deduped its joins with.
        if ($typeId && !$this->typeOrdered && !$this->aggregate && !$this->groupedPastTheKey() && $this->record::ordersByType()
            && $order = Type::find($typeId)?->recordsOrder()) {
            $this->orderByType($order);
        }

        return parent::runSelect();
    }

    private function groupedPastTheKey(): bool
    {
        $key = $this->record?->getKeyName() ?? 'id';
        $keys = [$key, $this->record?->qualifyColumn($key)];

        foreach ((array) $this->groups as $group) {
            if (!in_array($group, $keys, true)) {
                return true;
            }
        }

        return false;
    }

    /** The type's own ORDER BY, once: runSelect() adds it for a class that opts in, selectableRecords() for any. */
    public function orderByType(string $order): static
    {
        if (!$this->typeOrdered) {
            $this->typeOrdered = true;
            $this->orderByRaw($order);
        }

        return $this;
    }

    /**
     * ⚠ Not select()'s job alone: `get([$column])` and the `first([$column])` behind value()
     * write the list straight onto the builder, so the two paths each need the treatment.
     */
    public function get($columns = ['*'])
    {
        return parent::get($this->selectList($columns));
    }

    public function addSelect($column)
    {
        return parent::addSelect($this->columns($column));
    }

    public function where($column, ...$rest)
    {
        if ($path = $this->relationPath($column)) {
            return $this->whereRelationPath($path, ...$rest);
        }

        // ⚠ An ARRAY of conditions untouched: Laravel re-enters where() for each key on a nested
        // query of ours, and walking it here would read its VALUES as names.
        return parent::where(is_array($column) ? $column : $this->columns($column), ...$rest);
    }

    public function whereIn($column, ...$rest)
    {
        return parent::whereIn($this->columns($column), ...$rest);
    }

    public function whereNotIn($column, ...$rest)
    {
        return parent::whereNotIn($this->columns($column), ...$rest);
    }

    public function whereNull($columns, ...$rest)
    {
        return parent::whereNull($this->columns($columns), ...$rest);
    }

    public function whereNotNull($columns, ...$rest)
    {
        return parent::whereNotNull($this->columns($columns), ...$rest);
    }

    /** whereDate() and its four siblings: Laravel builds these without passing through where(). */
    protected function addDateBasedWhere($type, $column, $operator, $value, $boolean = 'and')
    {
        return parent::addDateBasedWhere($type, $this->columns($column), $operator, $value, $boolean);
    }

    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false)
    {
        return parent::whereBetween($this->columns($column), $values, $boolean, $not);
    }

    /** ⚠ Both columns are names, and the two-argument form hands the second one in as the operator. */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        if (!is_array($first) && $second === null && $this->invalidOperator($operator)) {
            [$second, $operator] = [$operator, '='];
        }

        return parent::whereColumn(is_array($first) ? $first : $this->columns($first), $operator, $this->columns($second), $boolean);
    }

    public function whereLike($column, $value, $caseSensitive = false, $boolean = 'and', $not = false)
    {
        return parent::whereLike($this->columns($column), $value, $caseSensitive, $boolean, $not);
    }

    /** A `<select>.<column>` path sorts through the correlated subquery where() and orderByRaw() use. */
    public function orderBy($column, ...$rest)
    {
        if ($path = $this->relationPath($column)) {
            $direction = strtolower((string) ($rest[0] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

            return parent::orderByRaw($this->relationPathSql($path).' '.$direction);
        }

        return parent::orderBy($this->columns($column), ...$rest);
    }

    /**
     * ⚠ Walks `<select column>.<column>` paths ONLY, quoted literals skipped: the rest of a raw
     * ORDER BY reaches MySQL as written, which is where the ORM's general walk went wrong.
     */
    public function orderByRaw($sql, $bindings = [])
    {
        $sql = preg_replace_callback('/\'(?:[^\'\\\\]|\\\\.)*\'|\b\w+\.\w+\b/', function (array $match): string {
            $path = $match[0][0] === "'" ? null : $this->relationPath($match[0]);

            return $path ? $this->relationPathSql($path) : $match[0];
        }, $sql);

        return parent::orderByRaw($sql, $bindings);
    }

    public function groupBy(...$groups)
    {
        return parent::groupBy(...array_map($this->columns(...), $groups));
    }

    public function value($column)
    {
        return parent::value($this->columns($column));
    }

    public function pluck($column, $key = null)
    {
        return parent::pluck($this->columns($column), is_null($key) ? null : $this->columns($key));
    }

    /** FIND_IN_SET over a comma column, named by alias or by a `<select>.<column>` path as in where(). */
    public function whereFindInSet($column, $value, string $boolean = 'and'): static
    {
        if ($path = $this->relationPath($column)) {
            $sql = $this->relationPathSql($path);
        } else {
            $column = $this->columns($column);
            $sql = $this->grammar->wrap($this->record && !str_contains($column, '.')
                ? $this->record->getTable().'.'.$column
                : $column);
        }

        return $this->whereRaw('FIND_IN_SET(?, '.$sql.')', [$value], $boolean);
    }

    public function orWhereFindInSet($column, $value): static
    {
        return $this->whereFindInSet($column, $value, 'or');
    }

    /**
     * ⚠ The write path's counterpart to the alias walk above, and the one point BOTH halves of a
     * save pass through: Eloquent's own insert and update reach the base builder, and so does a
     * caller writing through the query directly.
     */
    public function insert(array $values)
    {
        return parent::insert(
            array_is_list($values) ? array_map($this->values(...), $values) : $this->values($values)
        );
    }

    public function insertGetId(array $values, $sequence = null)
    {
        return parent::insertGetId($this->values($values), $sequence);
    }

    public function update(array $values)
    {
        return parent::update($this->values($values));
    }

    /** ⚠ Laravel wraps the NAME into a raw `<name> + n` before update() sees a key to translate. */
    public function incrementEach(array $columns, array $extra = [])
    {
        return parent::incrementEach($this->keyedColumns($columns), $extra);
    }

    public function decrementEach(array $columns, array $extra = [])
    {
        return parent::decrementEach($this->keyedColumns($columns), $extra);
    }

    /** `max('position')` and the rest, whose column reaches the grammar through no method above. */
    public function aggregate($function, $columns = ['*'])
    {
        return parent::aggregate($function, $this->columns($columns));
    }

    /**
     * @param  array<string, mixed>  $columns
     * @return array<string, mixed>
     */
    private function keyedColumns(array $columns): array
    {
        return array_combine(array_map($this->columns(...), array_keys($columns)), $columns);
    }

    /**
     * ⚠ array_is_list, never `is_array(reset($values))` -- which is how Laravel's own insert()
     * tells one row from many, and reads a single row whose select_multi value IS an array as a
     * list of rows.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function values(array $values): array
    {
        return $this->record ? $this->record->valuesForDatabase($values) : $values;
    }

    /** @return array<int, mixed> */
    private function selectList($columns): array
    {
        $columns = (array) $this->columns(is_array($columns) ? $this->nestedSelectsAsKeys($columns) : $columns);

        // ⚠ An aggregate keeps exactly what it asked for: an added column is not a convenience
        // there but an ONLY_FULL_GROUP_BY error, which is the rule the ORM applies too. `*` has
        // the identity already, and a column ahead of it is ERROR 1064.
        if (in_array('*', $columns, true) || $this->selectsAnAggregate($columns)) {
            return $columns;
        }

        // ⚠ QUALIFIED with the table. A partial SELECT over a JOIN is otherwise ERROR 1052 --
        // `types` carries a type_id of its own -- and the caller's own columns arrive qualified,
        // so an unqualified twin is a duplicate as well as an ambiguity.
        $table = $this->record?->getTable();
        $identity = array_map(
            fn (string $column): string => $table ? $table.'.'.$column : $column,
            array_intersect(self::IDENTITY, $this->record?->getColumns() ?? [])
        );

        return array_values(array_unique(array_merge($identity, $columns)));
    }

    /**
     * The ORM's nested `'<relation>' => [columns]` named what to eager-load, and no grammar takes an
     * array. A relation read loads its own rows here, so the entry becomes the KEY that read needs.
     * @param  array<array-key, mixed>  $columns
     * @return list<mixed>
     */
    private function nestedSelectsAsKeys(array $columns): array
    {
        $flat = [];

        foreach ($columns as $key => $column) {
            if (!is_array($column)) {
                $flat[] = $column;
            } elseif (is_string($key) && $this->record) {
                foreach ([$key.'_id', $key.'_ids'] as $alias) {
                    if ($this->record->aliasToColumn($alias) !== $alias) {
                        $flat[] = $alias;
                    }
                }
            }
        }

        return $flat;
    }

    /**
     * ⚠ Reads through an Expression, which is the shape an aggregate ARRIVES in here: Eloquent
     * quotes a bare string as an identifier, so `COUNT(id) AS total` is only ever a DB::raw().
     *
     * @param  array<int, mixed>  $columns
     */
    private function selectsAnAggregate(array $columns): bool
    {
        foreach ($columns as $column) {
            if ($column instanceof Expression) {
                $column = $column->getValue($this->grammar);
            }
            if (is_string($column) && preg_match('/\b(COUNT|SUM|AVG|MIN|MAX|GROUP_CONCAT)\s*\(/i', $column)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ⚠ Strings only, and an ARRAY is walked rather than treated as one name: `whereNull([...])`
     * takes a list of columns. where()'s conditions never come here. A Closure and an Expression
     * pass through, both carrying their own.
     */
    private function columns($columns)
    {
        if (is_array($columns)) {
            return array_map($this->columns(...), $columns);
        }

        return $this->record && is_string($columns) ? $this->record->aliasToColumn($columns) : $columns;
    }

    /**
     * `<select column>.<column of the type it points at>`: how jp7io/classes' select search reaches
     * a combo column's own combo, a LEFT JOIN on the ORM. A correlated subquery here, never a join:
     * the field layer's columns arrive bare, and a joined records table carries every one of them.
     * @return array{0: string, 1: Type, 2: string}|null The select column, its type, that type's column.
     */
    private function relationPath($path): ?array
    {
        // ⚠ A column qualified by the record's OWN table is no path, and the bound-type scope sends
        // one on every query: read as a path, it built the type's relationship map to answer no.
        if (!$this->record || !is_string($path) || !preg_match('/^(\w+)\.(\w+)$/', $path, $match)
            || ($match[1] === $this->record->getTable() && in_array($match[2], $this->record->getColumns(), true))) {
            return null;
        }

        // ⚠ A static call's template carries no type_id: its type is the bound class's, as in runSelect().
        // Read RAW: the magic read of an absent key asks isRelation(), which builds that same map.
        $type = Type::find((int) ($this->record->getAttributes()['type_id'] ?? 0) ?: (int) $this->record::boundTypeId());
        $definitions = $type?->fieldDefinitions() ?? [];
        $column = $this->record->aliasToColumn($match[1]);
        // Or the RELATION's name, as graphql's FilterJSON and the ORM's joins spell it: `evento.nome`.
        if (!isset($definitions[$column])) {
            $column = $this->record->aliasToColumn($match[1].'_id');
        }
        $row = $definitions[$column] ?? null;
        if (!$row || str_starts_with($column, 'select_multi_')
            || in_array($row['xtra'], FieldDefinitions::getSelectTypeXtras(), true)) {
            return null;
        }

        $targetId = $type->selectTypeId($column);
        $target = $targetId ? Type::find($targetId) : null;

        return $target ? [$column, $target, $target->recordTemplate()->aliasToColumn($match[2])] : null;
    }

    /** @param array{0: string, 1: Type, 2: string} $path */
    private function whereRelationPath(array $path, ...$rest): static
    {
        [$column, $target, $targetColumn] = $path;
        [$operator, $value] = count($rest) === 1 ? ['=', $rest[0]] : [$rest[0] ?? null, $rest[1] ?? null];
        $matches = $this->connection->table($target->recordsTable().' as relation_path')
            ->select('relation_path.id')
            ->where('relation_path.type_id', $target->type_id)
            ->where('relation_path.'.$targetColumn, $operator, $value);

        if ($published = $this->publishedPathSql($target)) {
            $matches->whereRaw($published);
        }

        return parent::whereIn($column, $matches, $rest[2] ?? 'and');
    }

    /** @param array{0: string, 1: Type, 2: string} $path */
    private function relationPathSql(array $path): string
    {
        [$column, $target, $targetColumn] = $path;
        $grammar = $this->grammar;
        $published = $this->publishedPathSql($target);

        return '(select '.$grammar->wrap('relation_path.'.$targetColumn)
            .' from '.$grammar->wrapTable($target->recordsTable().' as relation_path')
            .' where '.$grammar->wrap('relation_path.id').' = '.$grammar->wrap($this->record->getTable().'.'.$column)
            .' and '.$grammar->wrap('relation_path.type_id').' = '.(int) $target->type_id
            .($published ? ' and '.$published : '').')';
    }

    /**
     * The target's published predicates while the switch is on: the ORM joined a path's record with
     * them in the ON, so a row pointing at an unpublished one matched nothing. ⚠ On the PREFIXED
     * alias, which is what the grammar wraps `relation_path` into; these predicates are raw.
     */
    private function publishedPathSql(Type $target): ?string
    {
        if (!Record::isPublishedFiltersEnabled()) {
            return null;
        }

        $prefix = $this->grammar->getTablePrefix();
        $sql = Record::getPublishedFilters($prefix.$target->recordsTable(), $prefix.'relation_path');

        return $sql === null ? null : preg_replace('/ AND $/', '', $sql);
    }
}
