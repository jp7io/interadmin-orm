<?php

namespace Jp7\Former;

trait RowTrait
{
    private $row;

    public function row()
    {
        $this->row = new Row();

        return $this->row;
    }

    public function closeRow()
    {
        $html = $this->row->close();
        $this->row = null;

        return $html;
    }
}
