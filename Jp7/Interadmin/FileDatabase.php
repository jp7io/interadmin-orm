<?php

namespace Jp7\Interadmin;

/*
 * ## Campos:
 * id_arquivo_banco (PK)
 * id_tipo
 * id
 * parte        (0, 2, 3 - Abas "arquivos")
 * tipo         (gif, bmp - Extensão)
 * keywords
 * thumb        (obsoleto)
 * zoom         (obsoleto)
 * lang
 * versao       (contagem de mudanças)
 * Adicionar campos: data, tamanho, deleted
 */

/**
 * @property string url
 */
class FileDatabase extends RecordAbstract
{
    use Downloadable;

    protected $_primary_key = 'id_arquivo_banco';
     /**
     * Contains the Type, i.e. the record with an 'id_tipo' equal to this record�s 'id_tipo'.
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
        return config('interadmin.storage.backend_path').'/upload/'.
            (empty($this->getType()->nome) ? '' : toId($this->getType()->nome).'/').
            $this->basename().
            ($this->versao ? '?v='.$this->versao : '');
    }

    public function basename()
    {
        return str_pad($this->id_arquivo_banco, 8, '0', STR_PAD_LEFT).'.'.$this->tipo;
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
        if (!$this->_tipo && $this->id_tipo) {
            $this->_tipo = Type::getInstance($this->id_tipo, [
                'db' => $this->_db
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
        if (!$this->_parent && $this->id) {
            $this->_parent = Record::getInstance($this->id, $options, $this->getType());
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

    public function getTableName()
    {
        return $this->getDb()->getTablePrefix().'arquivos_banco';
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
        return ['id_arquivo', 'id_tipo', 'id', 'parte', 'url', 'url_thumb', 'url_zoom', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem', 'deleted'];
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
