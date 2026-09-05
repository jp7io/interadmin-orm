<?php

namespace Jp7\InterAdmin;

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
 *
 * The nine below are getAttributesNames(), which is a FIXED list here: unlike Record, a log has no
 * field definitions and getAttributesAliases() is empty, so naming them is reading this class rather than
 * guessing at a tenant's field layout. A `date_` value arrives as a Date, getMutatedAttribute()
 * casting every string on that prefix.
 *
 * @property int $id_log  PK
 * @property int $id  Parent record ID
 * @property int $type_id
 * @property string $lang
 * @property string $action  One of the ACTION_* constants
 * @property string $ip
 * @property string $data  Free text: the user agent on a login, the view's start time
 * @property int $select_user  The InterAdmin user this log belongs to
 * @property Date $date_insert
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
     * Contains the Type, i.e. the record with an 'type_id' equal to this recordﾴs 'type_id'.
     *
     * @var Type
     */
    protected $_type;
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
        $this->setRawAttributes($attributes + ['id_log' => 0]);
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
     * Gets the Type object for this record, which is then cached on the $_type property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return Type
     */
    public function getType(array $options = [])
    {
        if (!$this->_type) {
            $this->_type = Type::getInstance($this->type_id, [
                'db_prefix' => $this->db_prefix,
                'db' => $this->_db,
                'class' => empty($options['class']) ? null : $options['class'],
                'default_namespace' => static::DEFAULT_NAMESPACE
            ]);
        }

        return $this->_type;
    }
    /**
     * Sets the Type object for this record, changing the $_type property.
     *
     * @param Type $type
     */
    public function setType($type): void
    {
        $this->type_id = $type->type_id;
        $this->_type = $type;
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
            $type = $this->getType();
            if ($this->id) {
                $this->_parent = Record::getInstance($this->id, $options, $type);
            }
        }

        return $this->_parent;
    }
    /**
     * Sets the parent Record object for this record, changing the $_parent property.
     *
     * @param Record $parent
     */
    public function setParent($parent): void
    {
        $this->id = $parent->id;
        $this->_parent = $parent;
    }

    public function getAttributesAliases(): array
    {
        return [];
    }
    public function getAttributesFields(): array
    {
        return [];
    }
    public function getAttributesNames(): array
    {
        return ['id_log', 'id', 'type_id', 'lang', 'action', 'ip', 'data', 'select_user', 'date_insert'];
    }
    public function getTableName(): string
    {
        return $this->getDb()->getTablePrefix().'logs';
    }
    /**
     * @see RecordAbstract::getFieldType()
     */
    public function getFieldType($field): void
    {
        return;
    }
    public function getTagFilters(): string
    {
        return '';
    }
    /**
     * @see RecordAbstract::getAdminAttributes()
     */
    public function getAdminAttributes(): array
    {
        return [];
    }
    public static function create(array $attributes = []): self
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

    /**
     * @return static[]
     */
    public static function findLogs(array $options = []): array
    {
        $instance = new self;
        $aggregating = false;
        if (isset($options['fields'])) {
            // ⚠ Neither under a GROUP BY nor beside an aggregate. Which row's log id would it
            // be? MySQL answers with an arbitrary one and ONLY_FULL_GROUP_BY refuses the
            // question, so such a caller gets what it asked for and nothing else.
            $aggregating = isset($options['group']) || self::selectsAnAggregate($options['fields']);
            $options['fields'] = $aggregating
                ? (array) $options['fields']
                : array_merge(['id_log'], (array) $options['fields']);
        } else {
            $options['fields'] = static::DEFAULT_FIELDS;
        }
        $options['from'] = $instance->getTableName().' AS main';

        if (empty($options['where'])) {
            $options['where'][] = '1 = 1';
        }
        if (empty($options['order']) && !$aggregating) {
            $options['order'] = 'date_insert DESC';
        }
        // Internal use
        $options['aliases'] = $instance->getAttributesAliases();
        $options['field_definitions'] = $instance->getAttributesFields();

        $rs = $instance->_executeQuery($options);
        $logs = [];

        foreach ($rs as $row) {
            $log = new static(['id_log' => $row->id_log ?? 0]);
            $instance->_getAttributesFromRow($row, $log, $options);
            $logs[] = $log;
        }

        return $logs;
    }

    /**
     * The newest log matching $options, or null when nothing matches.
     *
     * Subscripting [0] unguarded raised "Undefined array key 0" for every caller whose
     * filter had no rows -- e.g. the dashboard's last-login lookup for a user with no
     * `login` entry, which 500'd the whole page. Both of its consumers (the warnings
     * check and pages/welcome/last-login.blade.php) already treat a falsy result as
     * "no previous login", so null is the contract they were written against.
     */
    public static function findFirstLog($options = [])
    {
        return static::findLogs($options + ['limit' => 1])[0] ?? null;
    }

    public static function getPublishedFilters($table, $alias): void
    {
        // N￣o precisa
    }
}
