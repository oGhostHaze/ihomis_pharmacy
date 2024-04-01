<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpdpayInPharmDrugStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_stock_logs', function (Blueprint $table) {
            $table->decimal('opdpay', 12, 2)->nullable()->default(0);
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
            $table->dropColumn('opdpay');
        });
    }
}
