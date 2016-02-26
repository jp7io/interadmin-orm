<?php

namespace Jp7\Interadmin;

use BadMethodCallException;

class TypelessQuery extends Query
{
    /**
     * @return Record[]
     */
    public function all()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->provider->deprecatedTypelessFind($this->options);
    }

    public function first()
    {
        return $this->provider->deprecatedTypelessFind($this->options + ['limit' => 1])->first();
    }

    public function count()
    {
        throw new BadMethodCallException('Not implemented.');
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
        $records = $this->all();
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
        $records = $this->all();
        foreach ($records as $record) {
            $record->forceDelete();
        }

        return count($records);
    }
}
