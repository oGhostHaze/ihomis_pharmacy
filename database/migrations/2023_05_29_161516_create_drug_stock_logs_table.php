<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('loc_code');
            $table->string('dmdcomb', 30);
            $table->string('dmdctr', 30);
            $table->string('chrgcode', 30);
            $table->date('date_logged');
            $table->dateTime('time_logged');

            $table->dateTime('dmdprdte');
            $table->decimal('unit_cost', 12, 2)->nullable()->default(0);
            $table->decimal('unit_price', 12, 2)->nullable()->default(0);

            $table->decimal('beg_bal', 12, 2)->nullable()->default(0);
            $table->decimal('purchased', 12, 2)->nullable()->default(0);
            $table->decimal('transferred', 12, 2)->nullable()->default(0);
            $table->decimal('received', 12, 2)->nullable()->default(0);
            $table->decimal('charged_qty', 12, 2)->nullable()->default(0);
            $table->decimal('issue_qty', 12, 2)->nullable()->default(0);
            $table->decimal('return_qty', 12, 2)->nullable()->default(0);

            // $table->decimal('sc_pwd', 12, 2)->nullable()->default(0);
            $table->decimal('ems', 12, 2)->nullable()->default(0);
            $table->decimal('maip', 12, 2)->nullable()->default(0);
            $table->decimal('wholesale', 12, 2)->nullable()->default(0);
            $table->decimal('pay', 12, 2)->nullable()->default(0);
            // $table->decimal('medicare', 12, 2)->nullable()->default(0);
            $table->decimal('service', 12, 2)->nullable()->default(0);
            // $table->decimal('govt_emp', 12, 2)->nullable()->default(0);
            $table->decimal('caf', 12, 2)->nullable()->default(0);

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
        Schema::dropIfExists('pharm_drug_stock_logs');
    }
}
