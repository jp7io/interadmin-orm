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
            $options['fields'] = ['COUNT(type_id) AS count_type_id'];
        } elseif ($options['group'] == 'type_id') {
            // O COUNT() precisa trazer a contagem total em 1 linha
            // Caso exista GROUP BY type_id, ele traria em várias linhas
            // Esse é um tratamento especial apenas para o type_id
            $options['fields'] = ['COUNT(DISTINCT type_id) AS count_type_id'];
            unset($options['group']);
        } else {
            // Se houver GROUP BY com outro campo, retornará a contagem errada
            throw new \Exception('GROUP BY is not supported when using count().');
        }

        $rows = $this->provider->deprecatedGetChildren(['limit' => 2, 'skip' => 0] + $options);
        if (count($rows) > 1) {
            throw new \Exception('Could not resolve groupBy() before count().');
        }

        return isset($rows[0]->count_type_id) ? intval($rows[0]->count_type_id) : 0;
    }

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
        if (is_string($id) && !is_numeric($id)) {
            $this->options['where'][] = $this->_parseComparison('id_slug', '=', $id);
        } else {
            $this->options['where'][] = $this->_parseComparison('type_id', '=', $id);
        }

        return $this->first();
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
        $child->parent_type_id = $this->provider->type_id;
        $child->mostrar = 'S';

        return $child->fill($attributes);
    }

    public function create(array $attributes = [])
    {
        return $this->build($attributes)->save();
    }
}
