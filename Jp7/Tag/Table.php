<?php

class Jp7_Tag_Table extends Jp7_Tag_Container
{
    public function tr($attrs = [])
    {
        $tr = new Jp7_Tag_Tr($attrs);
        $this->add($tr);

        return $tr;
    }

    public static function fromArray($array)
    {
        $object = new self();
        foreach ($array as $row) {
            $tr = $object->tr();
            foreach ($row as $cell) {
                $tr->td($cell);
            }
        }

        return $object;
    }

    public function rotate()
    {
        $trs = $this->getItems();

        $out = [];
        foreach ($trs as $col_i => $row) {
            foreach ($row->getItems() as $row_i => $cell) {
                if (!$out[$row_i]) {
                    $out[$row_i] = new Jp7_Tag_Tr();
                }
                $out[$row_i]->add($cell);
            }
        }

        $this->setItems($out);
    }
}
