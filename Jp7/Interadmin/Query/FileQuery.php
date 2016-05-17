<?php

namespace Jp7\Interadmin\Query;

use Jp7\Interadmin\Record;

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

    public function get()
    {
        return $this->provider->getArquivos(Record::DEPRECATED_METHOD, $this->options);
    }

    public function first()
    {
        $this->options['limit'] = 1;

        return $this->provider->getArquivos(Record::DEPRECATED_METHOD, $this->options)->first();
    }

    public function build(array $attributes = [])
    {
        return $this->provider->deprecated_createArquivo($attributes);
    }

    public function create(array $attributes = [])
    {
        return $this->build($attributes)->save();
    }
}
