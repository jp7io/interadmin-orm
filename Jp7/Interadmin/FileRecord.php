<?php

namespace Jp7\Interadmin;

use Exception;

/**
 * Class which represents records on the table interadmin_{client name}_arquivos.
 */
class FileRecord extends RecordAbstract
{
    use Downloadable;

    protected $_primary_key = 'id_file';

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
     * @param int   $id_file This record's 'id_file'.
     * @param array $options    Default array of options. Available keys: db_prefix, fields.
     */
    public function __construct($id_file = 0)
    {
        $this->id_file = $id_file;
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
            if (!$this->type_id) {
                kd('no type_id, not implemented');
                $this->type_id = jp7_fields_values($this->getTableName(), 'id_file', $this->id_file, 'type_id');
            }
            $this->_tipo = Type::getInstance($this->type_id, [
                'db' => $this->_db,
                'class' => $options['class'],
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
        $this->type_id = $type->type_id;
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
    public function setParent($parent)
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
        return $this->legenda;
    }

    public function getName()
    {
        return $this->nome;
    }

    /**
     * Adds this file to the table _files_banco and sets it's $url with the new $id_file_banco.
     * '$this->url' needs to have the path to the temporary file and it must have a parent.
     *
     * @return Url New $url created with the $id_file_banco of the added record.
     *
     * @todo Create a class for _files_banco
     */
    public function addToArquivosBanco($upload_root = '../../upload/')
    {
        global $lang;
        // Inserindo no banco de arquivos
        $fieldsValues = [
            'type_id' => $this->type_id,
            'id' => $this->id,
            'tipo' => $this->getExtension(),
            'parte' => intval($this->parte),
            'keywords' => $this->nome,
            'lang' => $lang->lang,
        ];

        $banco = new FileDatabase(['db_prefix' => $this->db_prefix]);
        $id_file_banco = $banco->addFile($fieldsValues);

        // Descobrindo o caminho da pasta
        $parent = $this->getParent();
        if ($parent->getParent()) {
            $parent = $parent->getParent();
        }

        $folder = $upload_root.to_slug($parent->getType()->nome, '').'/';
        // Montando nova url
        $newurl = $folder.$id_file_banco.'.'.$fieldsValues['tipo'];

        // Mkdir if needed
        if (!is_dir(dirname($newurl))) {
            @mkdir(dirname($newurl));
            @chmod(dirname($newurl), 0777);
        }

        // Movendo arquivo tempor�rio
        if (!@rename($this->url, $newurl)) {
            $msg = 'Impossivel renomear arquivo "'.$this->url.'" para "'.$newurl.'".<br /> getcwd(): '.getcwd();
            if (!is_file($this->url)) {
                $msg .= '<br /> Arquivo '.basename($this->url).' nao existe.';
            }
            if (!is_dir(dirname($this->url))) {
                $msg .= '<br /> Diretorio '.dirname($this->url).' nao existe.';
            }
            if (!is_dir(dirname($newurl))) {
                $msg .= '<br /> Diretorio '.dirname($newurl).' nao existe.';
            }
            throw new Exception($msg);
        }

        $clientSideFolder = '../../upload/'.to_slug($parent->getType()->nome, '').'/';
        $this->url = $clientSideFolder.$id_file_banco.'.'.$fieldsValues['tipo'];

        // Movendo o thumb
        if ($this->url_thumb) {
            $newurl_thumb = $folder.$id_file_banco.'_t.'.$fieldsValues['tipo'];
            @rename($this->url_thumb, $newurl_thumb);
            $this->url_thumb = $newurl_thumb;
        }

        return $this->url;
    }

    public function getAttributesAliases()
    {
        return [];
    }
    public function getAttributesCampos()
    {
        return [];
    }

    public function getFillable()
    {
        return ['parte', 'url', 'url_thumb', 'url_zoom', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem'];
    }

    public function getAttributesNames()
    {
        return ['id_file', 'type_id', 'id', 'parte', 'url', 'url_thumb', 'url_zoom', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem', 'deleted_at'];
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
}
