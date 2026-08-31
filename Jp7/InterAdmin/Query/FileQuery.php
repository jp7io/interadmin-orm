<?php

namespace Jp7\InterAdmin\Query;

use Jp7\InterAdmin\FileRecord;
use BadMethodCallException;

class FileQuery extends BaseQuery
{
    protected function _isChar($field): bool
    {
        $chars = [
            'visible',
            'featured',
            'deleted',
            'link_blank',
        ];

        return in_array($field, $chars);
    }

    /**
     * @return FileRecord[]
     */
    protected function providerFind($options)
    {
        return $this->provider->deprecated_getFiles($options);
    }

    /**
     * @return FileRecord|null
     */
    public function build(array $attributes = [])
    {
        return $this->provider->deprecated_createFile($attributes);
    }

    /**
     * @return FileRecord|null
     */
    public function create(array $attributes = [])
    {
        return $this->build($attributes)->save();
    }

    public function count(): int
    {
        return count($this->provider->deprecated_getFiles(['fields' => 'file_id'] + $this->options));
    }

    /**
     * @param $id string|int
     * @return FileRecord|null
     */
    public function find($id)
    {
        if (func_num_args() != 1) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 1.');
        }
        if (is_array($id)) {
            throw new BadMethodCallException('Wrong argument on find(). If you´re trying to get records, use get() instead of find().');
        }
        if (!$id) {
            return null; // save a query
        }

        $this->options['where'][] = $this->_parseComparison('file_id', '=', $id);

        return $this->first();
    }
}
