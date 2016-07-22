<?php

namespace Jp7;

/**
 * Class for handling collections of objects.
 */
class CollectionUtil
{
    /**
     * Keys are strings.
     *
     * @param array  $array
     * @param string $clause
     *
     * @return array
     */
    public static function separate($array, $clause)
    {
        $separated = [];

        $properties = explode('.', $clause);
        foreach ($array as $item) {
            $key = $item;
            foreach ($properties as $property) {
                $key = @$key->$property;
            }
            $separated[$key][] = $item;
        }

        return $separated;
    }

    public static function getFieldsValues($array, $fields, $fields_alias)
    {
        if (count($array) > 0) {
            $first = reset($array);

            $tipo = $first->getType();
            $retornos = $tipo->find([
                'class' => 'Jp7\\Interadmin\\Record',
                'fields' => $fields,
                'fields_alias' => $fields_alias,
                'where' => ['id IN ('.implode(',', $array).')'],
                'order' => 'FIELD(id,'.implode(',', $array).')',
                //'debug' => true
            ]);
            foreach ($retornos as $key => $retorno) {
                $array[$key]->attributes = $retorno->attributes + $array[$key]->attributes;
            }
        }
    }

    public static function eagerLoad($records, $relationships)
    {
        if (!$records) {
            return false;
        }
        if (!is_array($relationships)) {
            $relationships = [$relationships];
        }
        $relation = array_shift($relationships);
        $model = reset($records);
        if ($data = $model->getType()->getRelationshipData($relation)) {
            if ($data['type'] == 'select') {
                // select.id = record.select_id
                $idsMap = [];
                foreach ($records as $record) {
                    $alias = $relation.'_id';
                    if (!isset($idsMap[$record->$alias])) {
                        $idsMap[$record->$alias] = [];
                    }
                    $idsMap[$record->$alias][] = $record;
                }
                
                $rows = $data['tipo']
                    ->records()
                    ->whereIn('id', array_keys($idsMap))
                    ->get();
                
                foreach ($rows as $row) {
                    foreach ($idsMap[$row->id] as $record) {
                        $record->setRelation($relation, $row);
                    }
                    unset($row);
                }
            } elseif ($data['type'] == 'children') {
                // child.parent_id = parent.id
                $data['tipo']->setParent(null);
                $children = $data['tipo']
                    ->records()
                    ->whereIn('parent_id', $records)
                    ->get();
                if ($relationships) {
                    self::eagerLoad($children, $relationships);
                }
                $children = self::separate($children, 'parent_id');

                foreach ($records as $record) {
                    $record->setRelation($relation, $children[$record->id] ?: []);
                }
            } else {
                throw new Exception('Unsupported relationship type: "'.$data['type'].'" for class '.get_class($model).' - ID: '.$model->id);
            }
        } else {
            throw new Exception('Unknown relationship: "'.$relation.'" for class '.get_class($model).' - ID: '.$model->id);
        }
    }
}
