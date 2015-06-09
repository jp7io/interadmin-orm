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
            $list = $list->all();
        }
        if ($list instanceof \Illuminate\Support\Collection) {
            $collection = $list;
            $list = [];

            foreach ($collection as $item) {
                $list[$item->id] = $item->getName();
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
        $this->options = ['' => $this->blank] + $this->options;
        parent::options($this->options);

        return parent::render();
    }
}
