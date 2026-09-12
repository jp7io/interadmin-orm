<?php

namespace InterAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use InterAdmin\Models\Record;
use InterAdmin\Models\Type;

/**
 * @method static static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder where(mixed $column, mixed $operator = null, mixed $value = null)
 */
class Tag extends Model
{
    protected $table = 'tags';
    protected $primaryKey = 'tag_id';

    /**
     * The whole row: a tag IS these three columns (`type_id` alone tags a type, plus `id` tags
     * one record), and the record form is the only thing that writes them.
     */
    protected $fillable = ['parent_id', 'type_id', 'id'];

    /** The table carries no created_at/updated_at -- it predates Eloquent by a decade. */
    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function record()
    {
        return $this->belongsTo(Record::class, 'id');
    }
}
