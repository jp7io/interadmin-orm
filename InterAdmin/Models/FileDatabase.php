<?php

namespace InterAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One object in the media library: the bytes are addressed by this row's own id and extension,
 * never by a stored filename. {@see getBasename()}
 *
 * ⚠ `file_database_id` is a STRING: `int(11) unsigned ZEROFILL` hands back "00000279450".
 * @property string $file_database_id
 * @property int $type_id
 * @property int $id
 * @property int $part
 * @property string $directory
 * @property string $kind
 * @property string $keywords
 * @property string $lang
 * @property int $version
 * @property int $width
 * @property int $height
 * @property int $pages
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property string $url
 * @property-read ?Type $type
 * @method static static findOrFail(mixed $id, array $columns = ['*'])
 */
class FileDatabase extends Model
{
    use SoftDeletes;

    protected $table = 'files_database';
    protected $primaryKey = 'file_database_id';

    /** ⚠ `string` keeps the padding `int(11) unsigned ZEROFILL` gives the key, which is what
     *  `livewire/_file-table.blade.php`'s octal warning guards. An int moves every `wire:key`. */
    protected $keyType = 'string';

    /** ⚠ The table carries `updated_at` alone: at its default Eloquent stamps a `created_at`
     *  this table has not got, which is a 42S22 on every insert. */
    const CREATED_AT = null;

    /** The section this file was uploaded under. `type_id` 0 is "none", so the guard is truthiness. */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    /**
     * ⚠ Reads `kind`, so it answers for the extension the row currently claims and not for the
     * file on disk. A row saved before its `kind` names `00279450.` while the bytes sit beside it.
     */
    public function getBasename(): string
    {
        return str_pad((int) $this->file_database_id, 8, '0', STR_PAD_LEFT).'.'.$this->kind;
    }

    /**
     * ⚠ This WRITES `directory` when the column is empty -- 124 of ci's 171,118 live rows -- so a
     * save that follows a read of `url` persists the fallback. Mirrored from the ORM's accessor
     * rather than closed: `FileDatabaseController::update()` reads `url` for the CDN purge one
     * line above its `save()`, so dropping the write is a behaviour change with its own sweep.
     */
    public function getUrlAttribute(): string
    {
        if ($this->directory === '' && $this->type_id && $this->type) {
            $this->directory = toId($this->type->name);
        }

        return config('interadmin.storage.backend_path').'/upload/'.
            ($this->directory ? $this->directory.'/' : '').
            $this->getBasename().
            ($this->version ? '?v='.$this->version : '');
    }
}
