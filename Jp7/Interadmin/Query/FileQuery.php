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
    public function get()
    {
        return $this->provider->deprecated_getArquivos($this->options);
    }

    /**
     * @return FileRecord|null
     */
    public function first()
    {
        $this->options['limit'] = 1;

        return $this->provider->deprecated_getArquivos($this->options)->first();
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

    /**
     * Set deleted = 'S' and update the records.
     *
     * @return int
     */
    public function delete()
    {
        return $this->provider->deprecated_deleteArquivos($this->options);
    }

    /**
     * Set deleted = 'S' and update the records.
     *
     * @return int
     */
    public function forceDelete()
    {
        $arquivos = $this->provider->deprecated_getArquivos($this->options);
        foreach ($arquivos as $arquivo) {
            $arquivo->forceDelete();
        }
        return count($arquivos);
    }
}
