<?php

namespace Jp7\Interadmin\Field;

class FieldGroup
{
    /**
     * @var FieldInterface[]
     */
    protected $fields;

    public function add(FieldInterface $field)
    {
        $this->fields[] = $field;
    }

    public function getEditTag()
    {
        $html = '';
        $first = $this->fields[0];
        if ($first instanceof TitField) {
            $html .= $first->openPanel();
        } else {
            $firstClass = (isset($first->nome_id) ? $first->nome_id.'-panel' : '');
            $html .= '<div class="panel panel-default '.$firstClass.'">'.
                        '<div class="panel-body">';
        }

        $html .= implode(PHP_EOL, array_map(function ($field) {
            // (string) is needed to force render
            // Former wants object to be created and rendered before creating next object
            // Without (string) the group doesn't get the class "required"
            return ($field instanceof TitField) ? '' : (string) $field->getEditTag();
        }, $this->fields));

        if ($first instanceof TitField) {
            $html .= $first->closePanel();
        } else {
            $html .= '</div>'.
                        '</div>';
        }

        return $html;
    }
}
