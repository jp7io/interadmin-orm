<?php

namespace Jp7\Interadmin;

/**
 * Handles the url of uploaded files retrieved from the database.
 */
class FileField
{
    use Downloadable;

    protected $_parent;
    /**
     * Creditos/Legenda da imagem.
     *
     * @var Record
     */
    public $text;
    /**
     * Url da imagem.
     *
     * @var
     */
    public $url;

    public function __construct($url, $text = '')
    {
        $this->url = $url;
        $this->text = $text;
    }
    public function __toString()
    {
        return $this->url;
    }

    /**
     * Retorna texto para ser usado no "alt" ou "title" da imagem.
     * Utiliza o campo "Creditos/Leg.:" do arquivo ou o varchar_key do Registro.
     *
     * @return string
     */
    public function getText()
    {
        if ($this->text) {
            $retorno = $this->text;
        } elseif ($parent = $this->getParent()) {
            $retorno = $parent->getName();
        }

        return $retorno;
    }
    /**
     * Returns $parent.
     *
     * @see FileField::$parent
     *
     * @return Record
     */
    public function getParent()
    {
        return $this->_parent;
    }
    /**
     * Sets $parent.
     *
     * @param Record $parent
     *
     * @see FileField::$parent
     */
    public function setParent($parent)
    {
        $this->_parent = $parent;
    }
}
