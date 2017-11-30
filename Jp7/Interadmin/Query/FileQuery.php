<?php

namespace Jp7\Interadmin\Query;

use Jp7\Interadmin\FileRecord;

class FileQuery extends BaseQuery
{
    protected function _isChar($field)
    {
        $chars = [
            'mostrar',
            'destaque',
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
        return $this->provider->deprecated_getArquivos($options);
    }

    /**
     * @return FileRecord|null
     */
    public function build(array $attributes = [])
    {
        return $this->provider->deprecated_createArquivo($attributes);
    }

    /**
     * @return FileRecord|null
     */
    public function create(array $attributes = [])
    {
        return $this->build($attributes)->save();
    }

    public function count()
    {
        return count($this->provider->deprecated_getArquivos(['fields' => 'id_arquivo'] + $this->options));
    }
}
