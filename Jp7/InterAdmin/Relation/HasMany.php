<?php

namespace Jp7\InterAdmin\Relation;

use Jp7\InterAdmin\Query;
use Jp7\InterAdmin\Record;
use InvalidArgumentException;

class HasMany
{
    private \Jp7\InterAdmin\Record $record;
    private $className;
    private $foreign_key;
    private $local_key;
    private $query;

    public function __construct(Record $record, $className, $foreign_key, $local_key)
    {
        $this->record = $record;
        $this->className = $className;
        $this->foreign_key = $foreign_key;
        $this->local_key = $local_key;
    }

    public function getRelationshipData(): array
    {
        $type = call_user_func([$this->className, 'type']);
        $aliases = $type->getFieldAliases();
        $alias = array_search($this->foreign_key, $aliases);
        if (!$alias) {
            throw new InvalidArgumentException('Unknown alias: '.$this->foreign_key);
        }

        $conditions = [
            // 'cursos.sede = id'
            $alias.' = main.'.$this->local_key,
        ];
        if ($this->query) {
            $where = $this->query->getOptionsArray()['where'];
            array_shift($where);
            $conditions = array_merge($conditions, $where);
        }

        return [
            'tipo' => $type,
            'conditions' => $conditions,
            'has_type' => false,
        ];
    }

    public function query()
    {
        if (!$this->query) {
            $this->query = call_user_func([$this->className, 'query']);
            $local_key = $this->local_key;
            $this->query->where($this->foreign_key, $this->record->$local_key);
        }

        return $this->query;
    }

    public function __call(string $method, array $arguments)
    {
        $response = call_user_func_array([$this->query(), $method], $arguments);
        if ($response instanceof Query) {
            return $this;
        }

        return $response;
    }
}
