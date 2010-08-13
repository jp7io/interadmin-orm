<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdmin
 */

/**
 * Handles the url of uploaded files retrieved from the database.
 * 
 * @package InterAdmin
 */
class InterAdminFieldFile {
	protected $_parent;
	/**
	 * Créditos/Legenda da imagem.
	 *
	 * @var InterAdmin
	 */
	public $text;
	/**
	 * Url da imagem.
	 * @var
	 */
	public $url;
	
	public function __construct($url, $text = '') {
		$this->url = $url;
		$this->text = $text;
	}
	public function __toString() {
		return $this->url;
	}
	public function getUrl() {
		return $this->url;
	}
	/**
	 * Retorna texto para ser usado no "alt" ou "title" da imagem.
     * Utiliza o campo "Créditos/Leg.:" do arquivo ou o varchar_key do Registro.
	 *
	 * @return string
	 */
    public function getText() {
        if ($this->text) {
			$retorno = $this->text;
		}
		if ($parent = $this->getParent()) {
			$fieldsAlias = constant(get_class($parent) . '::DEFAULT_FIELDS_ALIAS');
			$varchar_key = 'varchar_key';
			if ($fieldsAlias) {
				$varchar_key = $parent->getTipo()->getCamposAlias($varchar_key);
			}
			$retorno = $parent->$varchar_key;
		}
		return htmlspecialchars($retorno);
	}
	/**
     * Returns $parent.
     *
     * @see InterAdminFile::$parent
	 * @return InterAdmin
     */
    public function getParent() {
        return $this->_parent;
    }
    /**
     * Sets $parent.
     *
     * @param InterAdmin $parent
     * @see InterAdminFile::$parent
     */
    public function setParent($parent) {
        $this->_parent = $parent;
    }
}