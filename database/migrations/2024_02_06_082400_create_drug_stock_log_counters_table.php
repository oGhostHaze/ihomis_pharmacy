<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugStockLogCountersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_stock_log_counters', function (Blueprint $table) {
            $table->id();
            $table->date('date_start');
            $table->date('date_end')->nullable();
            $table->string('status', 1)->default('A');
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pharm_drug_stock_log_counters');
    }
}
