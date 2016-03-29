<?php

namespace Jp7\Interadmin\Field;

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
        $name = $this->getFormerName();
        if ($this->isEmail()) {
            $rules[$name][] = 'email';
        }
        if ($this->tamanho) {
            $rules[$name][] = 'max:'.$this->tamanho;
        }
        return $rules;
    }
    
    protected function isEmail()
    {
        return $this->xtra === 'email' || $this->xtra === 'id_email';
    }
    
    protected function getFormerField()
    {
        $input = parent::getFormerField();
        if ($this->isEmail()) {
            $input->type('email');
        }
        return $input->data_type($this->xtra ?: false);
    }

}
