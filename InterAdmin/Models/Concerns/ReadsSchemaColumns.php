<?php

namespace InterAdmin\Models\Concerns;

use Illuminate\Support\Facades\Cache;
use InterAdmin\Models\Type;

/** A model's nullable and numeric columns, read off its table's schema once and cached. */
trait ReadsSchemaColumns
{
    /** Column types whose empty value is 0 rather than ''. */
    private const NUMERIC_TYPES = ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint',
        'decimal', 'numeric', 'float', 'double', 'year', 'bit'];

    /** @return array<int, string> */
    public function getNullableColumns(): array
    {
        return $this->schemaColumns('nullable');
    }

    /** @return array<int, string> */
    public function getNumericColumns(): array
    {
        return $this->schemaColumns('numeric');
    }

    /**
     * ⚠ Unlike getColumns() this caches an EMPTY answer: no nullable column is every table before
     * increment 14, not a failed read. One schema read fills both entries.
     *
     * @return array<int, string>
     */
    private function schemaColumns(string $which): array
    {
        $table = $this->getConnection()->getTablePrefix().$this->getTable();

        if (is_array($cached = Cache::get($which.',,'.$table))) {
            return $cached;
        }

        $all = $this->getConnection()->getSchemaBuilder()->getColumns($this->getTable());

        if (!$all) {
            return [];
        }

        $lists = ['nullable' => [], 'numeric' => []];

        foreach ($all as $column) {
            if (!empty($column['nullable'])) {
                $lists['nullable'][] = $column['name'];
            }
            if (in_array($column['type_name'] ?? '', self::NUMERIC_TYPES, true)) {
                $lists['numeric'][] = $column['name'];
            }
        }

        foreach ($lists as $name => $list) {
            Cache::put($name.',,'.$table, $list, Type::CACHE_TTL);
        }

        return $lists[$which];
    }
}
