<?php

namespace Jp7\Interadmin\Query;

use Jp7\Interadmin\Type;
use BadMethodCallException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TypeQuery extends BaseQuery
{
    /**
     * @var Type
     */
    protected $provider;

    public function __construct(Type $provider = null)
    {
        if (is_null($provider)) {
            $provider = new Type;
        }
        parent::__construct($provider);
    }

    protected function providerFind($options)
    {
        return $this->provider->deprecatedGetChildren($options);
    }

    public function count()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }
        $options = $this->options;

        if (empty($options['group'])) {
            $options['fields'] = ['COUNT(id_tipo) AS count_id_tipo'];
        } elseif ($options['group'] == 'id_tipo') {
            // O COUNT() precisa trazer a contagem total em 1 linha
            // Caso exista GROUP BY id_tipo, ele traria em várias linhas
            // Esse é um tratamento especial apenas para o id_tipo
            $options['fields'] = ['COUNT(DISTINCT id_tipo) AS count_id_tipo'];
            unset($options['group']);
        } else {
            // Se houver GROUP BY com outro campo, retornará a contagem errada
            throw new \Exception('GROUP BY is not supported when using count().');
        }

        $rows = $this->provider->deprecatedGetChildren(['limit' => 2, 'skip' => 0] + $options);
        if (count($rows) > 1) {
            throw new \Exception('Could not resolve groupBy() before count().');
        }

        return isset($rows[0]->count_id_tipo) ? intval($rows[0]->count_id_tipo) : 0;
    }

    public function find($id)
    {
        if (func_num_args() != 1) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 1.');
        }

        if (is_array($id)) {
            throw new BadMethodCallException('Wrong argument on find(). If you´re trying to get records, use get() instead of find().');
        }

        if (is_string($id) && !is_numeric($id) && $id) {
            $this->options['where'][] = $this->_parseComparison('id_slug', '=', $id);
        } else {
            $this->options['where'][] = $this->_parseComparison('id_tipo', '=', $id);
        }

        return $this->provider->deprecatedGetChildren($this->options)->first();
    }

    public function findOrFail($id)
    {
        $result = $this->find($id);
        if (!$result) {
            throw new ModelNotFoundException('Unable to find a record with id: '.$id);
        }

        return $result;
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
