<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPullOutQtyInPharmDrugStockLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_stock_logs', function (Blueprint $table) {
            $table->decimal('pullout_qty', 12, 2)->nullable()->default(0);
        });
        Schema::table('pharm_drug_stock_logs_copy', function (Blueprint $table) {
            $table->decimal('pullout_qty', 12, 2)->nullable()->default(0);
        });
        Schema::table('pharm_drug_manual_log_items', function (Blueprint $table) {
            $table->decimal('pullout_qty', 12, 2)->nullable()->default(0);
        });
        Schema::table('pharm_drug_stock_cards', function (Blueprint $table) {
            $table->decimal('pullout_qty', 12, 2)->nullable()->default(0);
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
            $table->dropColumn('pullout_qty');
        });
        Schema::table('pharm_drug_stock_logs_copy', function (Blueprint $table) {
            $table->dropColumn('pullout_qty');
        });
        Schema::table('pharm_drug_manual_log_items', function (Blueprint $table) {
            $table->dropColumn('pullout_qty');
        });
        Schema::table('pharm_drug_stock_cards', function (Blueprint $table) {
            $table->dropColumn('pullout_qty');
        });
    }
}
