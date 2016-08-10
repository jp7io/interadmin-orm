<?php

namespace Jp7\Interadmin\Field;

use Former;

class SelectField extends ColumnField
{
    use SelectFieldTrait;

    protected $id = 'select';

    const XTRA_RECORD = '0';
    const XTRA_RECORD_RADIO = 'radio';
    const XTRA_RECORD_AJAX = 'ajax';
    const XTRA_TYPE = 'S';
    const XTRA_TYPE_RADIO = 'radio_tipos';
    const XTRA_TYPE_AJAX = 'ajax_tipos';

    public function getCellHtml()
    {
        return $this->formatTextRelated(true);
    }

    public function getText()
    {
        return $this->formatTextRelated(false);
    }

    protected function formatTextRelated($html)
    {
        $currentRecords = $this->getCurrentRecords();
        if (count($currentRecords)) {
            $related = $currentRecords[0];
        } else {
            $related = $this->getValue(); // to show only an ID
        }
        return $this->formatText($related, $html);
    }

    public function hasTipo()
    {
        return in_array($this->xtra, [self::XTRA_TYPE, self::XTRA_TYPE_AJAX, self::XTRA_TYPE_RADIO]);
    }

    public function hasMassEdit()
    {
        return true;
    }

    protected function getFormerField()
    {
        return Former::select($this->getFormerName())
            ->id($this->getFormerId())
            ->value($this->getValue())
            ->options($this->getOptions());
    }

    protected function getFilterField()
    {
        return $this->getFormerField();
    }

    public function getFilterTag()
    {
        $options = ['blank' => '(vazio)'] + $this->getOptions();
        return $this->getFilterField()
            ->name('filtro_'.$this->getFormerName())
            ->options($options)
            ->removeClass('form-control')
            ->addClass('filter-select')
            ->data_allow_blank()
            ->raw();
    }
}
