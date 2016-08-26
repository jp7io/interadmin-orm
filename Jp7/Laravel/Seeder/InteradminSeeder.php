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
            if (!starts_with($line, 'INSERT INTO')) {
                if (starts_with($line, '--') ||
                    starts_with($line, '/*') ||
                    starts_with($line, 'LOCK TABLES') ||
                    starts_with($line, 'UNLOCK TABLES') ||
                    !trim($line)) {
                    continue;
                }
                throw new Exception('Invalid SQL: '.$line);
            }
            DB::unprepared($line);
        }
        $file = null;
    }
}
