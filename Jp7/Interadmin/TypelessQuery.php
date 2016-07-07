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
        throw new BadMethodCallException('Not implemented.');
    }

    public function findOrFail($id)
    {
        throw new BadMethodCallException('Not implemented.');
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
