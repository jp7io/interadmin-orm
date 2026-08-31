<?php

namespace Jp7\InterAdmin;

/**
 * @property int $file_database_id  PK
 * @property int $type_id
 * @property int $id    Parent record ID
 * @property int $part  0, 2, 3 - the arquivos tabs
 * @property string $kind gif, bmp - the extension
 * @property string $keywords
 * @property string $thumb obsoleto
 * @property string $zoom obsoleto
 * @property string $lang
 * @property int $versao  contagem de mudanças
 * @property Date $date_modify
 * @property string $directory noticias, mediabox, can't be the type's name because it can change
 * @property int $width
 * @property int $height
 * @property int $pages   PDF page count, 0 where nothing counted it
 * @property string $deleted   'S' or ''
 * @property string $url    getUrlAttribute() mutator
 */
class FileDatabase extends RecordAbstract
{
    use Downloadable;

    protected $_primary_key = 'file_database_id';
    /** A fixed column list, so `file_` is an ordinary prefix here and `file_database_id` is an int. */
    protected $hasFileFields = false;

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

    public function __construct(array $attributes = [])
    {
        $this->setRawAttributes($attributes);
    }

    /**
     * @return string
     */
    public function getUrlAttribute(): string
    {
        if ($this->directory === '' && $this->getType()) {
            // TODO: remove after migration
            $this->directory = toId($this->getType()->name);
        }
        return config('interadmin.storage.backend_path').'/upload/'.
            ($this->directory ? $this->directory.'/' : '').
            $this->getBasename().
            ($this->versao ? '?v='.$this->versao : '');
    }

    public function setDateModifyAttribute($value): void
    {
        $this->attributes['date_modify'] = new \Date($value);
    }

    public function getBasename(): string
    {
        return str_pad((int) $this->file_database_id, 8, '0', STR_PAD_LEFT).'.'.$this->kind;
    }

    public function save()
    {
        $this->attributes['date_modify'] = new \Date;
        return parent::save();
    }

    /**
     * Gets the Type object for this record, which is then cached on the $_type property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return Type
     */
    public function getType()
    {
        if (!$this->_type && $this->attributes['type_id']) {
            $this->_type = Type::getInstance($this->attributes['type_id'], [
                'db' => $this->_db
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
        $this->attributes['type_id'] = $type->type_id;
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
        if (!$this->_parent && $this->attributes['id']) {
            $this->_parent = Record::getInstance($this->attributes['id'], $options, $this->getType());
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
        $this->attributes['id'] = $parent->id;
        $this->_parent = $parent;
    }

    public function getTableName(): string
    {
        return $this->getDb()->getTablePrefix().'files_database';
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
        return ['id_arquivo', 'type_id', 'id', 'part', 'url', 'url_thumb', 'url_zoom', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem', 'deleted'];
    }

    public function getTagFilters(): string
    {
        return '';
    }
    /**
     * @see RecordAbstract::getFieldType()
     */
    public function getFieldType($field): void
    {
        return;
    }
    /**
     * @see RecordAbstract::getAdminAttributes()
     */
    public function getAdminAttributes(): array
    {
        return [];
    }
}
