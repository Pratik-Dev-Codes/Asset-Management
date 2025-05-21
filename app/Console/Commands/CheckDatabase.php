<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDatabase extends Command
{
    protected $signature = 'db:check';
    protected $description = 'Check database tables';

    public function handle()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $this->info('Database connection successful!');
            $this->info('Tables in database:');
            
            $dbName = DB::getDatabaseName();
            $tableKey = 'Tables_in_' . $dbName;
            
            $tableNames = array_map(function($table) use ($tableKey) {
                return $table->$tableKey;
            }, $tables);
            
            $this->table(['Table Name'], array_map(function($name) {
                return ['Table Name' => $name];
            }, $tableNames));
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Database error: ' . $e->getMessage());
            return 1;
        }
    }
}
