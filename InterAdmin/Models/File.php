<?php

namespace InterAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use InterAdmin\Models\Concerns\StoresEmptyNumbersAsZero;
use InterAdmin\Models\Record;

class File extends Model
{
    use StoresEmptyNumbersAsZero;

    public $timestamps = false;

    protected $table = 'files';
    protected $primaryKey = 'file_id';

    public function record()
    {
        return $this->belongsTo(Record::class, 'id');
    }
}
