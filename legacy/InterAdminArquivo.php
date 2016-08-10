<?php

use Jp7\Interadmin\RecordAbstract;
use Jp7_Interadmin_Upload as Upload;

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
 * Class which represents records on the table interadmin_{client name}_arquivos.
 */
class InterAdminArquivo extends RecordAbstract implements InterAdminAbstract
{
    const DEFAULT_NAMESPACE = '';
    protected $_primary_key = 'id_arquivo';

    /**
     * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
     *
     * @var string
     */
    public $db_prefix;
    /**
     * Contains the InterAdminTipo, i.e. the record with an 'id_tipo' equal to this record´s 'id_tipo'.
     *
     * @var InterAdminTipo
     */
    protected $_tipo;
    /**
     * Contains the parent InterAdmin object, i.e. the record with an 'id' equal to this record's 'parent_id'.
     *
     * @var InterAdmin
     */
    protected $_parent;
    /**
     * Public Constructor. If $options['fields'] was passed the method $this->getFieldsValues() is called.
     *
     * @param int   $id_arquivo This record's 'id_arquivo'.
     * @param array $options    Default array of options. Available keys: db_prefix, fields.
     */
    public function __construct($id_arquivo = 0, $options = [])
    {
        $this->id_arquivo = $id_arquivo;

        if ($options['fields']) {
            $this->getFieldsValues($options['fields']);
        }
    }
    /**
     * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return InterAdminTipo
     */
    public function getTipo($options = [])
    {
        if (!$this->_tipo) {
            if (!$this->id_tipo) {
                $this->id_tipo = jp7_fields_values($this->getTableName(), 'id_arquivo', $this->id_arquivo, 'id_tipo');
            }
            $this->_tipo = InterAdminTipo::getInstance($this->id_tipo, [
                'db_prefix' => $this->db_prefix,
                'db' => $this->_db,
                'class' => $options['class'],
            ]);
        }

        return $this->_tipo;
    }
    /**
     * Sets the InterAdminTipo object for this record, changing the $_tipo property.
     *
     * @param InterAdminTipo $tipo
     */
    public function setTipo($tipo)
    {
        $this->id_tipo = $tipo->id_tipo;
        $this->_tipo = $tipo;
    }

    public function getType($options = [])
    {
        return $this->getTipo($options);
    }
    /**
     * Sets the Type object for this record, changing the $_tipo property.
     *
     * @param Type $tipo
     */
    public function setType($tipo)
    {
        $this->setTipo($tipo);
    }

    /**
     * Gets the parent InterAdmin object for this record, which is then cached on the $_parent property.
     *
     * @param array $options Default array of options. Available keys: db_prefix, table, fields, fields_alias, class.
     *
     * @return InterAdmin
     */
    public function getParent($options = [])
    {
        if (!$this->_parent) {
            $tipo = $this->getTipo();
            if ($this->id || $this->getFieldsValues('id')) {
                $this->_parent = InterAdmin::getInstance($this->id, $options, $tipo);
            }
        }

        return $this->_parent;
    }
    /**
     * Sets the parent InterAdmin object for this record, changing the $_parent property.
     *
     * @param InterAdmin $parent
     */
    public function setParent($parent)
    {
        $this->id = $parent->id;
        $this->_parent = $parent;
    }
    /**
     * Returns the full url address of this file.
     *
     * @return string
     */
    public function getUrl()
    {
        global $config;
        $url = ($this->url) ? $this->url : $this->getFieldsValues('url');
        $url = str_replace('../../', $config->url, $url);

        return $url;
    }
    /**
     * Returns the description of this file.
     *
     * @return string
     */
    public function getText()
    {
        return htmlspecialchars($this->getFieldsValues('legenda'));
    }
    /**
     * Adds this file to the table _arquivos_banco and sets it's $url with the new $id_arquivo_banco.
     * '$this->url' needs to have the path to the temporary file and it must have a parent.
     *
     * @return Url New $url created with the $id_arquivo_banco of the added record.
     *
     * @todo Create a class for _arquivos_banco
     */
    public function addToArquivosBanco($uploadPath = 'upload/')
    {
        global $lang;
        // Inserindo no banco de arquivos
        $fieldsValues = [
            'id_tipo' => $this->id_tipo,
            'id' => $this->id,
            'tipo' => $this->getExtension(),
            'parte' => intval($this->parte),
            'keywords' => $this->nome,
            'lang' => $lang->lang,
        ];

        $banco = new InterAdminArquivoBanco(['db_prefix' => $this->db_prefix]);
        $id_arquivo_banco = $banco->addFile($fieldsValues);

        // Descobrindo o caminho da pasta
        $parent = $this->getParent();
        if ($parent->getParent()) {
            $parent = $parent->getParent();
        }

        $filepath = toId($parent->getTipo()->nome).'/'.$id_arquivo_banco.'.'.$fieldsValues['tipo'];

        // Movendo arquivo temporário
        if (starts_with($this->url, '../../upload')) {
            $oldpath = replace_prefix('../../', '', $this->url);
            Storage::move($oldpath, $uploadPath.$filepath);
        } else {
            Storage::put($uploadPath.$filepath, file_get_contents($this->url), 'public');
            unlink($this->url);
        }

        // Montando nova url
        $clientSideFolder = '../../upload/';
        $this->url = $clientSideFolder.$filepath;

        return $this->url;
    }

    /**
     * Returns the extension of this file.
     *
     * @return string Extension, such as 'jpg' or 'gif'.
     */
    public function getExtension()
    {
        $url = reset(explode('?', $this->url));

        return preg_replace('/(.*)\.(.*)$/', '\2', $url);
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
    public function getTableName()
    {
        if ($this->id_tipo) {
            return $this->getTipo()->getArquivosTableName();
        } else {
            return $this->db_prefix.'_arquivos';
        }
    }

    /**
     * @see InterAdminAbstract::getCampoTipo()
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
     * @see InterAdminAbstract::getAdminAttributes()
     */
    public function getAdminAttributes()
    {
        return [];
    }
    public function getSize()
    {
        return Upload::getHumanSize($this->url);
    }

    /**
     * Sets this object´s attributes with the given array keys and values.
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false)
    {
        if ($forceAsString) {
            throw new Exception('Not implemented');
        }
        if (is_array($fields)) {
            $retorno = (object) [];
            // returns only the fields requested on $fields
            foreach ($fields as $key => $value) {
                if (is_array($value)) {
                    $retorno->$key = $this->$key;
                } else {
                    $retorno->$value = $this->$value;
                }
            }
            return $retorno;
        }
        return $this->$fields;
    }
}
