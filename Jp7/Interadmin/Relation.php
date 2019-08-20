<?php

namespace Jp7\Interadmin;

class Relation
{
    public static function eagerLoad($records, $relationships, $selectStack = null)
    {
        if (is_array($records)) {
            $records = jp7_collect($records);
        }
        if (!count($records)) {
            return;
        }
        if (!is_array($relationships)) {
            $relationships = [$relationships];
        }
        $relation = array_shift($relationships);
        $model = $records->first();
        if ($relation === '_parent') {
            return self::eagerLoadParent($records, $relationships, $relation, $selectStack);
        }
        if ($data = $model->getType()->getRelationshipData($relation)) {
            if ($data['type'] == 'select') {
                if ($data['multi']) {
                    return self::eagerLoadSelectMulti($records, $relationships, $relation, $data, $selectStack);
                }
                return self::eagerLoadSelect($records, $relationships, $relation, $data, $selectStack);
            } elseif ($data['type'] == 'children') {
                return self::eagerLoadChildren($records, $relationships, $relation, $data, $selectStack);
            }
            throw new \Exception('Unsupported relationship type: "'.$data['type'].'" for class '.get_class($model).' - ID: '.$model->id);
        }
        throw new \Exception('Unknown relationship: "'.$relation.'" for class '.get_class($model).' - ID: '.$model->id);
    }

    protected static function eagerLoadParent($records, $relationships, $relation, $selectStack = null)
    {
        $select = $selectStack ? array_shift($selectStack) : [];
        if ($records->first()->hasLoadedParent()) {
            if ($relationships) { // still has things to eager load
                $rows = $records->map->getParent()->filter();
                self::eagerLoad($rows, $relationships, $selectStack);
            }
            return; // already loaded
        }

        $parentTypeIds = [];
        foreach ($records as $record) {
            $record->loadAttributes(['parent_id', 'parent_id_tipo'], false);
            if (!$record->parent_id_tipo) {
                continue;
            }
            $parentTypeIds[$record->parent_id_tipo] = $parentTypeIds[$record->parent_id_tipo] ?? [];
            $parentTypeIds[$record->parent_id_tipo][$record->parent_id] = true;
        }

        $rows = [];
        foreach ($parentTypeIds as $parentTypeId => $parentIdMap) {
            $parentRecords = Type::getInstance($parentTypeId)
                ->records()
                ->select($select)
                ->whereIn('id', array_keys($parentIdMap))
                ->get();
            if (property_exists($records, '_refs')) {
                $records->_refs[] = $parentRecords; // avoid $rows be removed from memory too soon
            }
            foreach ($parentRecords as $parent) {
                $rows[$parent->id_tipo.','.$parent->id] = $parent;
            }
        }

        if ($relationships) { // still has things to eager load
            self::eagerLoad($rows, $relationships, $selectStack);
        }

        foreach ($records as $record) {
            $key = $record->parent_id_tipo.','.$record->parent_id;
            if (isset($rows[$key])) {
                $record->setParent($rows[$key]);
            }
        }
    }

    protected static function eagerLoadSelectMulti($records, $relationships, $relation, $data, $selectStack = null)
    {
        $select = $selectStack ? array_shift($selectStack) : [];
        if ($records->first()->hasLoadedRelation($relation)) {
            if ($relationships) { // still has things to eager load
                $rows = $records->pluck($relation)->flatten();
                self::eagerLoad($rows, $relationships, $selectStack);
            }
            return; // already loaded
        }

        // select_multi.id IN (record.select_multi_ids)
        $ids = [];
        $alias = $relation.'_ids';
        foreach ($records as $record) {
            $fks = $record->$alias;
            $fksArray = is_array($fks) ? $fks : array_filter(explode(',', $fks));
            $ids = array_merge($ids, $fksArray);
        }
        $ids = array_unique($ids);
        if ($ids) {
            if ($data['has_type']) {
                $rows = jp7_collect([]);
                foreach ($ids as $id) {
                    $rows[$id] = Type::getInstance($id);
                }
            } else {
                $rows = (clone $data['query'])
                    ->select($select)
                    ->whereIn('id', $ids)
                    ->get();
                if (property_exists($records, '_refs')) {
                    $records->_refs[] = $rows; // avoid $rows be removed from memory too soon
                }
                $rows = $rows->keyBy('id');
            }
            if ($relationships) { // still has things to eager load
                self::eagerLoad($rows, $relationships, $selectStack);
            }
        }
        foreach ($records as $record) {
            $loaded = (object) [
                'fks' => $record->$alias,
                'values' => jp7_collect([])
            ];
            if (isset($rows)) {
                $fksArray = is_array($loaded->fks) ? $loaded->fks : array_filter(explode(',', $loaded->fks));
                foreach ($fksArray as $fk) {
                    if (isset($rows[$fk])) {
                        $loaded->values[] = $rows[$fk];
                    }
                }
            }
            $record->setRelation($relation, $loaded);
        }
    }

    protected static function eagerLoadSelect($records, $relationships, $relation, $data, $selectStack = null)
    {
        $select = $selectStack ? array_shift($selectStack) : [];
        if ($records->first()->hasLoadedRelation($relation)) {
            if ($relationships) { // still has things to eager load
                $rows = $records->pluck($relation)->filter();
                self::eagerLoad($rows, $relationships, $selectStack);
            }
            return; // already loaded
        }

        // select.id = record.select_id
        $alias = $relation.'_id';
        $ids = $records->pluck($alias)->unique()->filter()->all();
        if (!$ids) {
            return;
        }

        if ($data['has_type']) {
            $rows = jp7_collect([]);
            foreach ($ids as $id) {
                $rows[$id] = Type::getInstance($id);
            }
        } else {
            $rows = (clone $data['query'])
                ->select($select)
                ->whereIn('id', $ids)
                ->get();
            if (property_exists($records, '_refs')) {
                $records->_refs[] = $rows; // avoid $rows be removed from memory too soon
            }
            $rows = $rows->keyBy('id');
        }
        if ($relationships) { // still has things to eager load
            self::eagerLoad($rows, $relationships, $selectStack);
        }
        foreach ($records as $record) {
            $id = $record->$alias;
            $record->setRelation($relation, $rows[$id] ?? null);
        }
    }

    protected static function eagerLoadChildren($records, $relationships, $relation, $data, $selectStack = null)
    {
        $select = $selectStack ? array_shift($selectStack) : [];
        if ($records->first()->hasLoadedRelation($relation)) {
            if ($relationships) { // still has things to eager load
                $rows = $records->pluck($relation)->flatten();
                self::eagerLoad($rows, $relationships, $selectStack);
            }
            return; // already loaded
        }

        // child.parent_id = parent.id
        $data['tipo']->setParent(null);
        $children = $data['tipo']
            ->records()
            ->select($select)
            ->whereIn('parent_id', $records->all())
            ->get();
        if ($relationships) { // still has things to eager load
            self::eagerLoad($children, $relationships, $selectStack);
        }
        if (property_exists($records, '_refs')) {
            $records->_refs[] = $children; // avoid $children be removed from memory too soon
        }
        $children = $children->groupBy('parent_id');

        foreach ($records as $record) {
            if (!isset($children[$record->id])) {
                $children[$record->id] = jp7_collect();
            }
            foreach ($children[$record->id] as $child) {
                $child->setParent($record);
            }
            $record->setRelation($relation, $children[$record->id]);
        }
    }
}
