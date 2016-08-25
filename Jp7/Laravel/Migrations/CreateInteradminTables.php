<?php

namespace Jp7\Laravel\Migrations;

use Illuminate\Database\Migrations\Migration;
use DB;

class CreateInteradminTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::unprepared($this->getSchemaSql());
    }

    public function down()
    {
        preg_match_all('/CREATE TABLE `([^`]+)`/', $this->getSchemaSql(), $matches);
        $tables = $matches[1];
        foreach ($tables as $table) {
            DB::unprepared('DROP TABLE IF EXISTS `'.$table.'`');
        }
    }

    private function getSchemaSql()
    {
        return file_get_contents(database_path('interadmin_schema.sql'));
    }
}
