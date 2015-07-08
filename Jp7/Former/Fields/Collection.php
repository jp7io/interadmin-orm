<?php

namespace Jp7\Former\Fields;

class Collection extends \Former\Form\Fields\Select
{
    protected $blank = 'Selecione';
    protected $options = [];

    public function blank($text)
    {
        $this->blank = $text;

        return $this;
    }

    public function options($list, $selected = null, $valuesAsKeys = false)
    {
        if ($list instanceof \Jp7\Interadmin\Query\Base) {
            throw new \Exception('Usar ->lists(x,id)');
        }
        if ($list instanceof \Jp7\Interadmin\Collection) {
            if ($list->first() instanceof \InterAdminAbstract) {
                throw new \Exception('Usar ->lists(x,id)');
            }
        }
        $this->options = $list;
        
        if (!is_null($selected)) {
            $this->value = $selected;
        }
        
        return $this;
    }

    public function render()
    {
        if ($this->options instanceof \Illuminate\Support\Collection) {
            $this->options = $this->options->all();
        }
        
        $this->options = ['' => $this->blank] + $this->options;
        parent::options($this->options);

        return parent::render();
    }
}
