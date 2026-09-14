<?php

namespace InterAdmin\Models;

use Jp7\InterAdmin\Schema\TypeCache;

/**
 * Every `types` row's tree columns as ONE type-tag entry, which any type write forgets whole: a child's
 * save moves its parent's list. Unfiltered, the published switch being runtime state.
 */
final class TypeIndex
{
    private const KEY = 'type_index';

    private const COLUMNS = ['type_id', 'parent_type_id', 'model_type_id', 'name', 'id_slug', 'position', 'admin', 'menu', 'visible', 'deleted_at'];

    /** @var array<int, array<string, mixed>>|null keyed by type_id, in `position, name, type_id` order */
    private static ?array $rows = null;

    /** @var array<int, list<int>>|null child ids per parent, in the rows' order */
    private static ?array $children = null;

    /** @return array<int, array<string, mixed>> */
    public static function rows(): array
    {
        return self::$rows ??= TypeCache::store()->remember(self::KEY, TypeCache::TTL, fn () => self::load());
    }

    /** @return array<string, mixed>|null */
    public static function row(int $typeId): ?array
    {
        return self::rows()[$typeId] ?? null;
    }

    /**
     * $parentId's children in listedChildTypes()'s order, under its published switch.
     * @return list<int>
     */
    public static function childIds(int $parentId, bool $published): array
    {
        if (self::$children === null) {
            self::$children = [];
            foreach (self::rows() as $id => $row) {
                self::$children[(int) $row['parent_type_id']][] = $id;
            }
        }

        $ids = self::$children[$parentId] ?? [];

        return $published ? array_values(array_filter($ids, fn (int $id) => self::isPublished(self::$rows[$id]))) : $ids;
    }

    /**
     * The types built on $modelTypeId as their model, by type_id. ⚠ Compared as integers, as MySQL
     * compares the column with an int: it is smallint on ci and varchar on the seeded tenant.
     * @return list<int>
     */
    public static function idsUsingModel(int $modelTypeId, bool $published): array
    {
        $ids = [];

        foreach (self::rows() as $id => $row) {
            if ((int) $row['model_type_id'] === $modelTypeId && (!$published || self::isPublished($row))) {
                $ids[] = $id;
            }
        }
        sort($ids);

        return $ids;
    }

    /** The types table's published filter, which listedChildTypes() and the published scope both apply. */
    public static function isPublished(array $row): bool
    {
        return (int) $row['visible'] === 1 && $row['deleted_at'] === null;
    }

    /** A write's forget: the entry, and this process's copy of it. */
    public static function forget(): void
    {
        TypeCache::forget(self::KEY);
        self::forgetMemo();
    }

    /** The unit-of-work boundary: the entry stays and the next read takes it again. */
    public static function forgetMemo(): void
    {
        self::$rows = null;
        self::$children = null;
    }

    /** @return array<int, array<string, mixed>> */
    private static function load(): array
    {
        $rows = [];
        $query = Type::query()->toBase()->select(self::COLUMNS)->orderBy('position')->orderBy('name')->orderBy('type_id');

        foreach ($query->get() as $row) {
            $rows[(int) $row->type_id] = (array) $row;
        }

        return $rows;
    }
}
