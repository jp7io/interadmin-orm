<?php

class InterAdminFieldFile {
	protected $_parent;
	/**
	 * Créditos/Legenda da imagem.
	 * @var
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
	public function getText() {
		return $this->text;
	}
	/**
     * Returns $parent.
     *
     * @see InterAdminFile::$parent
     */
    public function getParent() {
        return $this->_parent;
    }
    /**
     * Sets $parent.
     *
     * @param object $parent
     * @see InterAdminFile::$parent
     */
    public function setParent($parent) {
        $this->_parent = $parent;
    }
}