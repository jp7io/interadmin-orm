<?php

namespace Jp7\Interadmin;

class Relation
{
    public static function eagerLoad($records, $relationships, $selectStack = null)
    {
        if (!is_array($records)) {
            $records = $records->all();
        }
        if (!$records) {
            return;
        }
        if (!is_array($relationships)) {
            $relationships = [$relationships];
        }
        $relation = array_shift($relationships);
        $model = reset($records);
        if ($data = $model->getType()->getRelationshipData($relation)) {
            if ($data['type'] == 'select') {
                if ($data['multi']) {
                    self::eagerLoadSelectMulti($records, $relationships, $relation, $data, $selectStack);
                } else {
                    self::eagerLoadSelect($records, $relationships, $relation, $data, $selectStack);
                }
            } elseif ($data['type'] == 'children') {
                self::eagerLoadChildren($records, $relationships, $relation, $data, $selectStack);
            } else {
                throw new \Exception('Unsupported relationship type: "'.$data['type'].'" for class '.get_class($model).' - ID: '.$model->id);
            }
        } else {
            throw new \Exception('Unknown relationship: "'.$relation.'" for class '.get_class($model).' - ID: '.$model->id);
        }
    }

    protected static function eagerLoadSelectMulti($records, $relationships, $relation, $data, $selectStack = null)
    {
        $select = $selectStack ? array_shift($selectStack) : [];
        if (reset($records)->hasLoadedRelation($relation)) {
            if ($relationships) {
                $rows = collect(array_column($records, $relation))->flatten();
                self::eagerLoad($rows, $relationships, $selectStack);
            }
            return;
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
                ->get()
                ->keyBy('id');
        }
        if ($relationships) {
            self::eagerLoad($rows, $relationships, $selectStack);
        }
        foreach ($records as $record) {
            $loaded = (object) [
                'fks' => $record->$alias,
                'values' => jp7_collect([])
            ];
            $fksArray = is_array($loaded->fks) ? $loaded->fks : array_filter(explode(',', $loaded->fks));
            foreach ($fksArray as $fk) {
                if (isset($rows[$fk])) {
                    $loaded->values[] = $rows[$fk];
                }
            }
            $record->setRelation($relation, $loaded);
        }
    }

    protected static function eagerLoadSelect($records, $relationships, $relation, $data, $selectStack = null)
    {
        $select = $selectStack ? array_shift($selectStack) : [];
        if (reset($records)->hasLoadedRelation($relation)) {
            if ($relationships) {
                $rows = array_filter(array_column($records, $relation));
                self::eagerLoad($rows, $relationships, $selectStack);
            }
            return;
        }

        // select.id = record.select_id
        $alias = $relation.'_id';
        $ids = array_filter(array_unique(array_column($records, $alias)));
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
                ->get()
                ->keyBy('id');
        }
        if ($relationships) {
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
        if (reset($records)->hasLoadedRelation($relation)) {
            if ($relationships) {
                $rows = collect(array_column($records, $relation))->flatten();
                self::eagerLoad($rows, $relationships, $selectStack);
            }
            return;
        }

        // child.parent_id = parent.id
        $data['tipo']->setParent(null);
        $children = $data['tipo']
            ->records()
            ->select($select)
            ->whereIn('parent_id', $records)
            ->get();
        if ($relationships) {
            self::eagerLoad($children, $relationships, $selectStack);
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
