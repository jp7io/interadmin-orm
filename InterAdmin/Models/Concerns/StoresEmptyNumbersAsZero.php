<?php

namespace InterAdmin\Models\Concerns;

/** ⚠ A form posts '' for an unset number, which strict mode refuses for an integer column: 0 is stored. */
trait StoresEmptyNumbersAsZero
{
    use ReadsSchemaColumns;

    public function setAttribute($key, $value)
    {
        if ($value === '' && in_array($key, $this->getNumericColumns(), true)) {
            $value = 0;
        }

        return parent::setAttribute($key, $value);
    }
}
