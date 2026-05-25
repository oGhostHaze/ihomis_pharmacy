<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGamotTagToPharmDrugStockTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_stock_issues', function (Blueprint $table) {
            $table->decimal('gamot', 12, 2)->nullable()->default(0);
        });

        Schema::table('pharm_drug_stock_logs', function (Blueprint $table) {
            $table->decimal('gamot', 12, 2)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_drug_stock_issues', function (Blueprint $table) {
            $table->dropColumn('gamot');
        });

        Schema::table('pharm_drug_stock_logs', function (Blueprint $table) {
            $table->dropColumn('gamot');
        });
    }
}
