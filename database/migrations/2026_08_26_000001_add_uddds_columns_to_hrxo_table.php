<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $statements = [
            "IF COL_LENGTH('dbo.hrxo', 'order_type') IS NULL
                ALTER TABLE dbo.hrxo ADD order_type VARCHAR(20) NULL;",
            "IF COL_LENGTH('dbo.hrxo', 'is_uddds') IS NULL
                ALTER TABLE dbo.hrxo ADD is_uddds BIT NOT NULL CONSTRAINT DF_hrxo_is_uddds DEFAULT 0;",
            "IF COL_LENGTH('dbo.hrxo', 'uddds_start_date') IS NULL
                ALTER TABLE dbo.hrxo ADD uddds_start_date DATE NULL;",
            "IF COL_LENGTH('dbo.hrxo', 'uddds_end_date') IS NULL
                ALTER TABLE dbo.hrxo ADD uddds_end_date DATE NULL;",
            "IF COL_LENGTH('dbo.hrxo', 'uddds_source_docointkey') IS NULL
                ALTER TABLE dbo.hrxo ADD uddds_source_docointkey VARCHAR(50) NULL;",
        ];

        foreach ($statements as $sql) {
            DB::connection('hospital')->unprepared($sql);
        }
    }

    public function down(): void
    {
        $statements = [
            "IF COL_LENGTH('dbo.hrxo', 'uddds_source_docointkey') IS NOT NULL
                ALTER TABLE dbo.hrxo DROP COLUMN uddds_source_docointkey;",
            "IF COL_LENGTH('dbo.hrxo', 'uddds_end_date') IS NOT NULL
                ALTER TABLE dbo.hrxo DROP COLUMN uddds_end_date;",
            "IF COL_LENGTH('dbo.hrxo', 'uddds_start_date') IS NOT NULL
                ALTER TABLE dbo.hrxo DROP COLUMN uddds_start_date;",
            "IF OBJECT_ID('dbo.DF_hrxo_is_uddds', 'D') IS NOT NULL
                ALTER TABLE dbo.hrxo DROP CONSTRAINT DF_hrxo_is_uddds;",
            "IF COL_LENGTH('dbo.hrxo', 'is_uddds') IS NOT NULL
                ALTER TABLE dbo.hrxo DROP COLUMN is_uddds;",
            "IF COL_LENGTH('dbo.hrxo', 'order_type') IS NOT NULL
                ALTER TABLE dbo.hrxo DROP COLUMN order_type;",
        ];

        foreach ($statements as $sql) {
            DB::connection('hospital')->unprepared($sql);
        }
    }
};
