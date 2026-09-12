<?php

namespace InterAdmin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InterAdmin\Models\Record;
use InterAdmin\Models\Type;

/**
 * One stored revision of a record, on a type with `versions` on.
 *
 * A row is written on every save (Record\VersionWriter::store) and is the ONLY thing
 * written when the editor lacks `publicacao` -- that is what "aguardando aprovação" means.
 */
class Version extends Model
{
    protected $table = 'versions';
    protected $primaryKey = 'version_id';
    // The table has neither created_at nor updated_at; updated_at carries the timestamp, copied
    // off the record like every other column.
    public $timestamps = false;

    public function record()
    {
        return $this->belongsTo(Record::class, 'id');
    }

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    /**
     * Versions of $type's records, from the table that actually holds them.
     */
    public static function forType(Type $type): Builder
    {
        return (new self)->setTable(self::tableFor($type))->newQuery();
    }

    /**
     * A type with its own `table_name` versions into `<table_name>_versions` beside it, not into the shared
     * one -- reading the shared table for such a type returns whatever record happens to share the
     * numeric id. Derived from the records table so the language prefix rides along.
     */
    public static function tableFor(Type $type): string
    {
        $records = Str::after($type->getInterAdminsTableName(), DB::getTablePrefix());

        return $type->table_name ? $records.'_versions' : Str::replaceLast('records', 'versions', $records);
    }
}
