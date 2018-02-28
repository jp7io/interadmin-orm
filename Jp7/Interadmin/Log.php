<?php

namespace Jp7\Interadmin;

use Date;
use Request;

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category JP7
 */

/**
 * Class representing records on the table interadmin_{client name}_logs.
 */
class Log extends RecordAbstract
{
    const ACTION_VIEW = 'view';
    const ACTION_LOGIN = 'login';
    const ACTION_INSERT = 'insert';
    const ACTION_MODIFY = 'modify';

    protected $_primary_key = 'id_log';

    /**
     * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
     *
     * @var string
     */
    public $db_prefix;
    /**
     * Contains the Type, i.e. the record with an 'id_tipo' equal to this recordﾴs 'id_tipo'.
     *
     * @var Type
     */
    protected $_tipo;
    /**
     * Contains the parent Record object, i.e. the record with an 'id' equal to this record's 'parent_id'.
     *
     * @var Record
     */
    protected $_parent;
    /**
     * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
     *
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes + ['id_log' => 0];
    }

    public function &__get($name)
    {
        $value = null;
        if (isset($this->attributes[$name])) {
            $value = &$this->attributes[$name];
        } elseif (in_array($name, $this->getAttributesNames())) {
            $this->loadAttributes($this->getAttributesNames(), false);
            $value = &$this->attributes[$name];
        }
        $value = $this->getMutatedAttribute($name, $value);
        return $value;
    }

    /**
     * Gets the Type object for this record, which is then cached on the $_tipo property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return Type
     */
    public function getType($options = [])
    {
        if (!$this->_tipo) {
            $this->_tipo = Type::getInstance($this->id_tipo, [
                'db_prefix' => $this->db_prefix,
                'db' => $this->_db,
                'class' => $options['class'],
                'default_namespace' => static::DEFAULT_NAMESPACE
            ]);
        }

        return $this->_tipo;
    }
    /**
     * Sets the Type object for this record, changing the $_tipo property.
     *
     * @param Type $tipo
     */
    public function setType($tipo)
    {
        $this->id_tipo = $tipo->id_tipo;
        $this->_tipo = $tipo;
    }
    /**
     * Gets the parent Record object for this record, which is then cached on the $_parent property.
     *
     * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class.
     *
     * @return Record
     */
    public function getParent($options = [])
    {
        if (!$this->_parent) {
            $tipo = $this->getType();
            if ($this->id) {
                $this->_parent = Record::getInstance($this->id, $options, $tipo);
            }
        }

        return $this->_parent;
    }
    /**
     * Sets the parent Record object for this record, changing the $_parent property.
     *
     * @param Record $parent
     */
    public function setParent($parent)
    {
        $this->id = $parent->id;
        $this->_parent = $parent;
    }

    public function getAttributesAliases()
    {
        return [];
    }
    public function getAttributesCampos()
    {
        return [];
    }
    public function getAttributesNames()
    {
        return ['id_log', 'id', 'id_tipo', 'lang', 'action', 'ip', 'data', 'select_user', 'date_insert'];
    }
    public function getTableName()
    {
        return $this->getDb()->getTablePrefix().'logs';
    }
    /**
     * @see RecordAbstract::getCampoTipo()
     */
    public function getCampoTipo($campo)
    {
        return;
    }
    public function getTagFilters()
    {
        return '';
    }
    /**
     * @see RecordAbstract::getAdminAttributes()
     */
    public function getAdminAttributes()
    {
        return [];
    }
    public static function create($attributes = [])
    {
        $log = new self;

        //$log->lang = $lang->lang;
        $log->ip = Request::ip();
        //$log->select_user = $s_user['id'];
        $log->date_insert = new Date;
        $log->fill($attributes);

        return $log;
    }

    public static function countLogs($options = [])
    {
        $logs = self::findLogs([
            'fields' => 'count(id)',
        ] + $options);

        return $logs[0]->count_id;
    }

    public static function findLogs($options = [])
    {
        $instance = new self;
        if (isset($options['fields'])) {
            $options['fields'] = array_merge(['id_log'], (array) $options['fields']);
        } else {
            $options['fields'] = static::DEFAULT_FIELDS;
        }
        $options['from'] = $instance->getTableName().' AS main';

        if (empty($options['where'])) {
            $options['where'][] = '1 = 1';
        }
        if (empty($options['order'])) {
            $options['order'] = 'date_insert DESC';
        }
        // Internal use
        $options['aliases'] = $instance->getAttributesAliases();
        $options['campos'] = $instance->getAttributesCampos();

        $rs = $instance->_executeQuery($options);
        $logs = [];

        foreach ($rs as $row) {
            $log = new static(['id_log' => $row->id_log]);
            $instance->_getAttributesFromRow($row, $log, $options);
            $logs[] = $log;
        }

        return $logs;
    }

    public static function findFirstLog($options = [])
    {
        return static::findLogs($options + ['limit' => 1])[0];
    }

    public static function getPublishedFilters($table, $alias)
    {
        // N￣o precisa
    }
}
