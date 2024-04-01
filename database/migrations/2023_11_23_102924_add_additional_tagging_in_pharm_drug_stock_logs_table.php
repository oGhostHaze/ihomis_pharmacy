<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalTaggingInPharmDrugStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_stock_logs', function (Blueprint $table) {
            $table->decimal('pcso', 12, 2)->nullable()->default(0);
            $table->decimal('phic', 12, 2)->nullable()->default(0);
            $table->decimal('konsulta', 12, 2)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_drug_stock_logs', function (Blueprint $table) {
            $table->dropColumn('pcso', 'phic', 'konsulta');
        });
    }
}