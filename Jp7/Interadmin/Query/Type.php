<?php

namespace Jp7\Interadmin\Query;

use Jp7\Interadmin\Record;
use Jp7\Interadmin\Type as InteradminType;
use BadMethodCallException;

class Type extends Base
{
    protected function _isChar($field)
    {
        $chars = [
            'mostrar',
            'language',
            'menu',
            'busca',
            'restrito',
            'admin',
            'editar',
            'unico',
            'versoes',
            'hits',
            'tags',
            'tags_list',
            'tags_tipo',
            'tags_registros',
            'publish_tipo',
            'visualizar',
            'deleted_tipo',
        ];

        return in_array($field, $chars);
    }

    public function all()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->provider->getChildren(Record::DEPRECATED_METHOD, $this->options);
    }

    public function first()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        $this->options['limit'] = 1;

        return $this->provider->getChildren(Record::DEPRECATED_METHOD, $this->options)->first();
    }

    public function build(array $attributes = [])
    {
        $className = InteradminType::getDefaultClass();

        $child = new $className();
        $child->parent_id_tipo = $this->provider->id_tipo;
        $child->mostrar = 'S';

        return $child->fill($attributes);
    }

    public function create(array $attributes = [])
    {
        return $this->build($attributes)->save();
    }
}
