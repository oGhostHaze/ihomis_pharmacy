<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRisInPharmDrugStockIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_stock_issues', function (Blueprint $table) {
            $table->boolean('ris')->nullable()->default(false);
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
            $table->dropColumn('ris');
        });
    }
}
