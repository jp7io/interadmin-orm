<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;

class VarcharField extends ColumnField
{
    protected $id = 'varchar';
    /*
    [0] = "Normal";
    ['id'] = "ID";
    ['id_email'] = "ID E-Mail";
    ['email'] = "E-Mail";
    ['num'] = "Número";
    ['cep'] = "CEP";
    ['cpf'] = "CPF";
    ['telefone'] = "Telefone";
    ['ll']="Latitude e Longitude";
    ['url'] = "URL";
    ['cor']="Cor Hexadecimal";
    */
    public function getRules()
    {
        $rules = parent::getRules();
        $name = $this->getRuleName();
        
        if ($this->isUnique()) {
            $rules[$name][] = $this->getUniqueRule();
        }
        if ($this->isEmail()) {
            $rules[$name][] = 'email';
        } elseif ($this->isNumeric()) {
            $rules[$name][] = 'numeric';
        } elseif ($this->isCep()) {
            $rules[$name][] = 'cep';
        } elseif ($this->isCpf()) {
            $rules[$name][] = 'cpf';
        }
        if ($this->tamanho) {
            $rules[$name][] = 'max:'.$this->tamanho;
        }
        return $rules;
    }
    
    protected function isUnique() {
        return $this->xtra === 'id' || $this->xtra === 'id_email';
    }
    
    protected function getUniqueRule() {
        // unique:table,column,except,idColumn
        $table = str_replace($this->type->db_prefix, '', $this->type->getInterAdminsTableName()); // FIXME
        return 'unique:'.$table.','.$this->tipo.','.$this->record->id;
    }
    
    protected function isEmail()
    {
        return $this->xtra === 'email' || $this->xtra === 'id_email';
    }
    
    protected function isNumeric()
    {
        return $this->xtra === 'num';
    }
    
    protected function isTel()
    {
        return $this->xtra === 'telefone';
    }
    
    protected function isCpf()
    {
        return $this->xtra === 'cpf';
    }
    
    protected function isCep()
    {
        return $this->xtra === 'cep';
    }
    
    protected function isColor()
    {
        return $this->xtra === 'cor';
    }
    
    protected function getFormerField()
    {
        $input = parent::getFormerField();
        if ($this->isEmail()) {
            $input->type('email');
        } elseif ($this->isTel()) {
            $input->type('tel');
        } elseif ($this->isColor()) {
            $input->prepend($this->getColorpickerHtml());
        }
        return $input->data_type($this->xtra ?: false);
    }
    
    public function getMassEditTag()
    {
        return Element::td($this->getFormerField()->raw())
            ->class($this->id);
    }

    protected function getColorpickerHtml()
    {
        return '<div class="colorpicker-button"></div>';
    }
}
