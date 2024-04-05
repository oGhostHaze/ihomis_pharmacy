<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocCodeToPharmDrugStockReorderLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_stock_reorder_levels', function (Blueprint $table) {
            $table->bigInteger('loc_code')->default('1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_drug_stock_reorder_levels', function (Blueprint $table) {
            $table->dropColumn('loc_code');
        });
    }
}
