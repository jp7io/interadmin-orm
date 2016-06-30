<?php

namespace Jp7\Interadmin\Query;

use Jp7\Interadmin\Record;
use Jp7\Interadmin\Type;
use BadMethodCallException;

class TypeQuery extends BaseQuery
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

    public function get()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->provider->deprecatedGetChildren($this->options);
    }

    public function first()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        $this->options['limit'] = 1;

        return $this->provider->deprecatedGetChildren(Record::DEPRECATED_METHOD, $this->options)->first();
    }
    
    public function count()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }
        $options = $this->options;
        $options['limit'] = 1;
        $options['fields'] = "COUNT(*)";
        
        $result = $this->provider->deprecatedGetChildren(Record::DEPRECATED_METHOD, $options)->first();
        return $result->count;
    }
    
    public function build(array $attributes = [])
    {
        $className = Type::getDefaultClass();

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
