<?php

namespace InterAdmin\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use InterAdmin\Models\Concerns\BuildsRecords;
use InterAdmin\Models\Record;
use InterAdmin\Models\Type;

/**
 * Every record of one type, whose doors build through Type::buildRecord() as ChildRecords' do.
 *
 * @extends HasMany<Record, Type>
 */
class TypeRecords extends HasMany
{
    use BuildsRecords;

    protected function newRecord(array $forced): Record
    {
        assert($this->parent instanceof Type);

        return $this->parent->buildRecord($forced);
    }
}
