<?php

/**
 * Class which represents records on the table interadmin_{client name}_arquivos.
 */
class InterAdminArquivo extends InterAdminAbstract
{
    use \Jp7\Interadmin\Downloadable;

    protected $_primary_key = 'id_arquivo';

    /**
     * Table prefix of this record. It is usually formed by 'interadmin_' + 'client name'.
     *
     * @var string
     */
    public $db_prefix;
    /**
     * Contains the InterAdminTipo, i.e. the record with an 'id_tipo' equal to this record�s 'id_tipo'.
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
    public function __construct($id_arquivo = 0)
    {
        $this->id_arquivo = $id_arquivo;
    }
    /**
     * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return InterAdminTipo
     */
    public function getType($options = [])
    {
        if (!$this->_tipo) {
            if (!$this->id_tipo) {
                kd('not implemented');
                $this->id_tipo = jp7_fields_values($this->getTableName(), 'id_arquivo', $this->id_arquivo, 'id_tipo');
            }
            $this->_tipo = InterAdminTipo::getInstance($this->id_tipo, [
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
    public function setType($tipo)
    {
        $this->id_tipo = $tipo->id_tipo;
        $this->_tipo = $tipo;
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
            $tipo = $this->getType();
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
     * Adds this file to the table _arquivos_banco and sets it's $url with the new $id_arquivo_banco.
     * '$this->url' needs to have the path to the temporary file and it must have a parent.
     *
     * @return Url New $url created with the $id_arquivo_banco of the added record.
     *
     * @todo Create a class for _arquivos_banco
     */
    public function addToArquivosBanco($upload_root = '../../upload/')
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

        $folder = $upload_root.to_slug($parent->getType()->nome, '').'/';
        // Montando nova url
        $newurl = $folder.$id_arquivo_banco.'.'.$fieldsValues['tipo'];

        // Mkdir if needed
        if (!is_dir(dirname($newurl))) {
            @mkdir(dirname($newurl));
            @chmod(dirname($newurl), 0777);
        }

        // Movendo arquivo tempor�rio
        if (!@rename($this->url, $newurl)) {
            $msg = 'Imposs�vel renomear arquivo "'.$this->url.'" para "'.$newurl.'".<br /> getcwd(): '.getcwd();
            if (!is_file($this->url)) {
                $msg .= '<br /> Arquivo '.basename($this->url).' n�o existe.';
            }
            if (!is_dir(dirname($this->url))) {
                $msg .= '<br /> Diret�rio '.dirname($this->url).' n�o existe.';
            }
            if (!is_dir(dirname($newurl))) {
                $msg .= '<br /> Diret�rio '.dirname($newurl).' n�o existe.';
            }
            throw new Exception($msg);
        }

        $clientSideFolder = '../../upload/'.to_slug($parent->getType()->nome, '').'/';
        $this->url = $clientSideFolder.$id_arquivo_banco.'.'.$fieldsValues['tipo'];

        // Movendo o thumb
        if ($this->url_thumb) {
            $newurl_thumb = $folder.$id_arquivo_banco.'_t.'.$fieldsValues['tipo'];
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
        return ['id_arquivo', 'id_tipo', 'id', 'parte', 'url', 'url_thumb', 'url_zoom', 'nome', 'legenda', 'creditos', 'link', 'link_blank', 'mostrar', 'destaque', 'ordem', 'deleted'];
    }
    public function getTableName()
    {
        if ($this->id_tipo) {
            return $this->getType()->getArquivosTableName();
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
}
