<?php

use HtmlObject\Element;

use Jp7_InterAdmin_Field_FieldInterface as FieldInterface;

class Jp7_InterAdmin_Field_Base implements FieldInterface {
    protected $ordem;
    protected $tipo;
    protected $nome;
    protected $ajuda;
    protected $tamanho;
    protected $obrigatorio;
    protected $separador;
    protected $xtra;
    protected $lista;
    protected $orderby;
    protected $combo;
    protected $readonly;
    protected $form;
    protected $label;
    protected $permissoes;
    protected $default;
    protected $nome_id;

    /**
     * @param array $campo
     */
    public function __construct(array $campo) {
        $this->tipo = $campo['tipo'];
        $this->nome = $campo['nome'];
        $this->ajuda = $campo['ajuda'];
        $this->tamanho = $campo['tamanho'];
        $this->obrigatorio = $campo['obrigatorio'];
        $this->separador = $campo['separador'];
        $this->xtra = $campo['xtra'];
        $this->lista = $campo['lista'];
        $this->orderby = $campo['orderby'];
        $this->combo = $campo['combo'];
        $this->readonly = $campo['readonly'];
        $this->form = $campo['form'];
        $this->label = $campo['label'];
        $this->permissoes = $campo['permissoes'];
        $this->default = $campo['default'];
        $this->nome_id = $campo['nome_id'];
    }

    public function getHeaderHtml()
    {
        return Element::th($this->getHeaderValue())
            ->title($this->tipo);
    }

    public function getListHtml(ADOFetchObj $record)
    {
        return Element::td($this->getListValue($record));
    }

    public function getHeaderValue()
    {
        return $this->nome;
    }

    public function getListValue(ADOFetchObj $record)
    {
        return $this->getValue($record);
    }
    
    protected function getValue(ADOFetchObj $record)
    {
        $column = $this->tipo;
        return $record->$column;
    }
}
