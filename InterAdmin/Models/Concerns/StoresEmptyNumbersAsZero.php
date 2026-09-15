<?php

namespace InterAdmin\Models\Concerns;

/** ⚠ Strict mode refuses '' for an integer column and null for a NOT NULL one: 0 is stored; a nullable column keeps its null. */
trait StoresEmptyNumbersAsZero
{
    use ReadsSchemaColumns;

    public function setAttribute($key, $value)
    {
        if (($value === '' || $value === null) && $this->takesZeroFor($key, $value)) {
            $value = 0;
        }

        return parent::setAttribute($key, $value);
    }

    /** A form posts '' for an unset number and code passes null; a nullable column keeps its null. */
    private function takesZeroFor(string $key, mixed $value): bool
    {
        if (!in_array($key, $this->getNumericColumns(), true)) {
            return false;
        }

        return $value === '' || !in_array($key, $this->getNullableColumns(), true);
    }
}
