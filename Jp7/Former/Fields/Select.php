<?php

namespace Jp7\Former\Fields;

class Select extends \Former\Form\Fields\Select
{
    public function render()
    {
       return str_replace('<option value="" disabled="disabled"', '<option value=""', parent::render());
    }
}
