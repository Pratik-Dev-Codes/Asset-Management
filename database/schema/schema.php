<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This file is for reference only - not an actual migration
    }

    /**
     * Get the SQL for the database schema.
     *
     * @return string
     */
    public static function getSchemaSql()
    {
        $schema = [];

        // Drop all tables if they exist
        $schema[] = 'SET FOREIGN_KEY_CHECKS=0;';

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $key = 'Tables_in_'.$databaseName;

        foreach ($tables as $table) {
            $tableName = $table->$key;
            $schema[] = "DROP TABLE IF EXISTS `$tableName`;";
        }

        // Create tables
        foreach ($tables as $table) {
            $tableName = $table->$key;
            $createTable = DB::selectOne("SHOW CREATE TABLE `$tableName`");
            $createTableSql = $createTable->{'Create Table'};
            $schema[] = $createTableSql.';';

            // Add table comments
            $tableComment = DB::selectOne('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$databaseName, $tableName]);

            if (! empty($tableComment->TABLE_COMMENT)) {
                $schema[] = "ALTER TABLE `$tableName` COMMENT = '{$tableComment->TABLE_COMMENT}';";
            }
        }

        // Add foreign key constraints
        $foreignKeys = DB::select('
            SELECT 
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
        ', [$databaseName]);

        foreach ($foreignKeys as $fk) {
            $schema[] = "ALTER TABLE `{$fk->TABLE_NAME}` 
                ADD CONSTRAINT `{$fk->CONSTRAINT_NAME}` 
                FOREIGN KEY (`{$fk->COLUMN_NAME}`) 
                REFERENCES `{$fk->REFERENCED_TABLE_NAME}` (`{$fk->REFERENCED_COLUMN_NAME}`);";
        }

        $schema[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n\n", $schema);
    }
}

// Generate the schema file
$schemaSql = DatabaseSchema::getSchemaSql();
file_put_contents(__DIR__.'/schema.sql', $schemaSql);

echo 'Schema file generated successfully at: '.__DIR__."/schema.sql\n";
