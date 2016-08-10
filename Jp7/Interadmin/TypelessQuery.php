<?php

namespace Jp7\Interadmin;

use BadMethodCallException;

class TypelessQuery extends Query
{
    /**
     * @return Record[]
     */
    public function get()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->provider->deprecatedTypelessFind($this->options);
    }

    public function first()
    {
        return $this->provider->deprecatedTypelessFind(['limit' => 1] + $this->options)->first();
    }

    public function count()
    {
        $options = $this->options;
        $options['fields'] = "COUNT(*)";
        $options['limit'] = 1;
        $result = $this->provider->deprecatedTypelessFind($options)->first();
        return $result->count;
    }

    public function find($id)
    {
        if (func_num_args() != 1) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 1.');
        }

        if (is_array($id)) {
            throw new BadMethodCallException('Wrong argument on find(). If you´re trying to get records, use all() instead of find().');
        }

        if (is_string($id) && !is_numeric($id)) {
            $this->options['where'][] = $this->_parseComparison('id_slug', '=', $id);
        } else {
            $this->options['where'][] = $this->_parseComparison('id', '=', $id);
        }

        return $this->first();
    }

    public function findMany($ids)
    {
        $sample = reset($ids);
        if (is_string($sample) && !is_numeric($sample)) {
            $key = 'id_slug';
        } else {
            $key = 'id';
        }

        $this->whereIn($key, $ids);

        return $this->provider->deprecatedTypelessFind($this->options);
    }

    public function delete()
    {
        $records = $this->get();
        foreach ($records as $record) {
            $record->delete();
        }

        return count($records);
    }

    /**
     * Remove permanently from the database.
     */
    public function forceDelete()
    {
        $records = $this->get();
        foreach ($records as $record) {
            $record->forceDelete();
        }

        return count($records);
    }
}
