<?php

namespace Jp7\InterAdmin;

use Exception;
use UnexpectedValueException;

/**
 * Class which represents records on the table interadmin_{client name}_files.
 */
class FileRecord extends RecordAbstract
{
    use Downloadable;

    protected $_primary_key = 'file_id';
    /** A fixed column list, so `file_` is an ordinary prefix here and `file_id` is an int. */
    protected $hasFileFields = false;

    /**
     * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
     *
     * @var string
     */
    public $db_prefix;
    /**
     * Contains the Type, i.e. the record with an 'type_id' equal to this record�s 'type_id'.
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
     * @param int   $file_id This record's 'file_id'.
     * @param array $options    Default array of options. Available keys: db_prefix, fields.
     */
    public function __construct($file_id = 0)
    {
        $this->file_id = $file_id;
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
            if (!$this->type_id) {
                throw new UnexpectedValueException(
                    'Cannot resolve the Type of file record '.$this->file_id.': it has no type_id. '.
                    'Call setType() before getType() -- looking the column up from the table was never '.
                    'implemented, it only called jp7io/inc, which this package no longer depends on.'
                );
            }
            $this->_type = Type::getInstance($this->type_id, [
                'db' => $this->_db,
                'class' => $options['class'] ?? null,
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
    /**
     * Returns the description of this file.
     *
     * @return string
     */
    public function getText()
    {
        return $this->caption;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAttributesAliases(): array
    {
        return [];
    }
    public function getAttributesFields(): array
    {
        return [];
    }

    public function getFillable(): array
    {
        return ['part', 'url', 'url_thumb', 'url_zoom', 'name', 'caption', 'credits', 'link', 'link_blank', 'visible', 'featured', 'position'];
    }

    public function getAttributesNames(): array
    {
        return ['file_id', 'type_id', 'id', 'part', 'url', 'url_thumb', 'url_zoom', 'name', 'caption', 'credits', 'link', 'link_blank', 'visible', 'featured', 'position', 'deleted'];
    }
    public function getTableName()
    {
        if ($this->type_id) {
            return $this->getType()->getArquivosTableName();
        } else {
            return $this->db_prefix.'_files';
        }
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
}
