<?php

namespace InterAdmin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A row of `<prefix>_logs`: who did what to which record, and when.
 *
 * ⚠ `id` is the LOGGED RECORD's id and `log_id` is the key, so a row is addressed by
 * (id, type_id) -- a record on the type's own table, which is why there is no relation here.
 *
 * @property int $log_id
 * @property int $id
 * @property int $type_id
 * @property string $lang
 * @property string $action One of the ACTION_* constants
 * @property string $ip
 * @property string $data Free text: the user agent on a login, the diff on a modify
 * @property int $select_user
 * @property ?\Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<self> where(mixed $column, mixed $operator = null, mixed $value = null)
 */
class Log extends Model
{
    const ACTION_VIEW = 'view';
    const ACTION_LOGIN = 'login';
    const ACTION_INSERT = 'insert';
    const ACTION_MODIFY = 'modify';

    /**
     * ⚠ There is no `updated_at` column; left at its default Eloquent writes one and 42S22s. And
     * `created_at` needs no cast entry: getDates() casts the CREATED_AT column on its own.
     */
    const UPDATED_AT = null;

    protected $table = 'logs';
    protected $primaryKey = 'log_id';

    /** ⚠ Nine framework columns fixed by the schema, with no tenant alias behind any of them. */
    protected $guarded = [];

    /**
     * A row stamped where the work STARTS rather than where it lands. ⚠ Not `create()`, which in
     * Eloquent INSERTS: both callers fill this row over a save and persist it at the end.
     */
    public static function open(array $attributes = []): self
    {
        return new self($attributes + ['ip' => request()->ip(), 'created_at' => now()]);
    }
}
