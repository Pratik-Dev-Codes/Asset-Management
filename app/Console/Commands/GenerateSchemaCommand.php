<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateSchemaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a database schema file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $key = 'Tables_in_' . str_replace('-', '_', $databaseName);
        
        $schema = [];
        
        // Start with disabling foreign key checks
        $schema[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $schema[] = '';
        
        // Generate DROP TABLE statements
        foreach ($tables as $table) {
            $tableName = $table->$key;
            $schema[] = "DROP TABLE IF EXISTS `$tableName`;";
        }
        
        $schema[] = '';
        
        // Generate CREATE TABLE statements
        foreach ($tables as $table) {
            $tableName = $table->$key;
            $createTable = DB::selectOne("SHOW CREATE TABLE `$tableName`");
            $createTableSql = $createTable->{'Create Table'};
            $schema[] = $createTableSql . ';';
            $schema[] = '';
        }
        
        // Add foreign key constraints
        $schema[] = '-- Foreign Key Constraints';
        $schema[] = '';
        
        $foreignKeys = DB::select("
            SELECT DISTINCT 
                TABLE_NAME, 
                COLUMN_NAME, 
                CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                REFERENCED_TABLE_SCHEMA = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName]);
        
        foreach ($foreignKeys as $fk) {
            $schema[] = "ALTER TABLE `{$fk->TABLE_NAME}` ";
            $schema[] = "  ADD CONSTRAINT `{$fk->CONSTRAINT_NAME}` ";
            $schema[] = "  FOREIGN KEY (`{$fk->COLUMN_NAME}`) ";
            $schema[] = "  REFERENCES `{$fk->REFERENCED_TABLE_NAME}` (`{$fk->REFERENCED_COLUMN_NAME}`);";
            $schema[] = '';
        }
        
        // Re-enable foreign key checks
        $schema[] = 'SET FOREIGN_KEY_CHECKS=1;';
        
        // Write to file
        $schemaContent = implode("\n", $schema);
        $path = database_path('schema.sql');
        
        File::put($path, $schemaContent);
        
        $this->info("Schema file generated at: " . $path);
        
        return 0;
    }
}
