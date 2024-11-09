<?php

namespace Jp7\Interadmin;

/**
 * @property int $id_file_banco  PK
 * @property int $type_id
 * @property int $id    Parent record ID
 * @property int $parte  0, 2, 3 - Abas "arquivos"
 * @property string $type gif, bmp - Extensão
 * @property string $keywords
 * @property string $thumb obsoleto
 * @property string $zoom obsoleto
 * @property string $lang
 * @property int $versao  contagem de mudanças
 * @property Date $updated_at
 * @property string $directory noticias, mediabox, can't be the type's name because it can change
 * @property int $width
 * @property int $height
 * @property string $deleted   'S' or ''
 * @property string $url    getUrlAttribute() mutator
 */
class FileDatabase extends RecordAbstract
{
    use Downloadable;

    protected $_primary_key = 'id_file_banco';
    /**
     * Contains the Type, i.e. the record with an 'type_id' equal to this record�s 'type_id'.
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

    public function __construct(array $attributes = [])
    {
        $this->setRawAttributes($attributes);
    }

    /**
     * @return string
     */
    public function getUrlAttribute()
    {
        if ($this->directory === '' && $this->getType()) {
            // TODO: remove after migration
            $this->directory = toId($this->getType()->nome);
        }
        return config('interadmin.storage.backend_path') . '/upload/' .
            ($this->directory ? $this->directory . '/' : '') .
            $this->getBasename() .
            ($this->versao ? '?v=' . $this->versao : '');
    }

    public function setDateModifyAttribute($value)
    {
        $this->attributes['updated_at'] = new \Date($value);
    }

    public function getBasename()
    {
        return str_pad($this->id_file_banco, 8, '0', STR_PAD_LEFT) . '.' . $this->tipo;
    }

    public function save()
    {
        $this->attributes['updated_at'] = new \Date;
        return parent::save();
    }

    /**
     * Gets the Type object for this record, which is then cached on the $_tipo property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return Type
     */
    public function getType()
    {
        if (!$this->_tipo && $this->attributes['type_id']) {
            $this->_tipo = Type::getInstance($this->attributes['type_id'], [
                'db' => $this->_db
            ]);
        }
        return $this->_tipo;
    }
    /**
     * Sets the Type object for this record, changing the $_tipo property.
     *
     * @param Type $type
     */
    public function setType($type)
    {
        $this->attributes['type_id'] = $type->type_id;
        $this->_tipo = $type;
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
    public function setParent($parent)
    {
        $this->attributes['id'] = $parent->id;
        $this->_parent = $parent;
    }

    public function getTableName()
    {
        return $this->getDb()->getTablePrefix() . 'files_banco';
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
        return ['id_file', 'type_id', 'id', 'parte', 'url', 'url_thumb', 'url_zoom', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem', 'deleted_at'];
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
}
