<?php

namespace Jp7\Laravel\Seeder;

use Illuminate\Database\Seeder;
use SplFileObject;
use DB;

class InteradminSeeder extends Seeder
{
    public function run()
    {
        $this->importSql(database_path('interadmin_tipos.sql'));
        $this->importSql(database_path('interadmin_records.sql'));
    }

    protected function importSql($filepath)
    {
        $file = new SplFileObject($filepath);
        while (!$file->eof()) {
            $line = $file->fgets();
            if (strpos($line, 'INSERT INTO') === false) {
                continue;
            }
            DB::unprepared($line);
        }
        $file = null;
    }
}
