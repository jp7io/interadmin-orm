<?php

namespace InterAdmin\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Jp7\InterAdmin\Schema\FieldDefinitions;

/**
 * What Jp7\InterAdmin\Query does to a record query before it runs, on Eloquent's builder: the
 * alias layer, and the identity columns a partial SELECT keeps.
 * ⚠ The column is parameter 0 of every method below, which is what makes them the set. One that
 * is missing compiles the alias as a column name -- a 42S22, not a wrong answer.
 */
final class RecordQuery extends Builder
{
    /**
     * ⚠ Kept on every partial SELECT, as Type::deprecatedFind() keeps them: a hydrated record
     * with no `type_id` cannot read its own alias map, so `select('<alias>')` would answer null
     * for the very name it asks by. Intersected with the table, `id_slug` being optional.
     */
    private const IDENTITY = ['id', 'type_id', 'id_slug'];

    private ?Record $record = null;

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

        return parent::where($this->columns($column), ...$rest);
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

    public function orderBy($column, ...$rest)
    {
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
        $columns = (array) $this->columns($columns);

        // ⚠ An aggregate keeps exactly what it asked for: an added column is not a convenience
        // there but an ONLY_FULL_GROUP_BY error, which is the rule the ORM applies too.
        if ($columns === ['*'] || $this->selectsAnAggregate($columns)) {
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
     * takes a list of columns, while `where([...])` takes conditions whose values are not names
     * and match no alias. A Closure and an Expression pass through, both carrying their own.
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
        if (!$this->record || !is_string($path) || !preg_match('/^(\w+)\.(\w+)$/', $path, $match)) {
            return null;
        }

        $type = Type::find((int) $this->record->type_id);
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

        return parent::whereIn($column, $matches, $rest[2] ?? 'and');
    }

    /** @param array{0: string, 1: Type, 2: string} $path */
    private function relationPathSql(array $path): string
    {
        [$column, $target, $targetColumn] = $path;
        $grammar = $this->grammar;

        return '(select '.$grammar->wrap('relation_path.'.$targetColumn)
            .' from '.$grammar->wrapTable($target->recordsTable().' as relation_path')
            .' where '.$grammar->wrap('relation_path.id').' = '.$grammar->wrap($this->record->getTable().'.'.$column)
            .' and '.$grammar->wrap('relation_path.type_id').' = '.(int) $target->type_id.')';
    }
}
