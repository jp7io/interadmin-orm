<?php

namespace InterAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use InterAdmin\Models\Record;

class File extends Model
{
    public $timestamps = false;

    protected $table = 'files';
    protected $primaryKey = 'file_id';

    public function record()
    {
        return $this->belongsTo(Record::class, 'id');
    }
}
