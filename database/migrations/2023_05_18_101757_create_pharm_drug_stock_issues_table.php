<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmDrugStockIssuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_stock_issues', function (Blueprint $table) {
            $table->id();
            $table->string('stock_id', 20);
            $table->string('docointkey');
            $table->string('dmdcomb', 30); //drug and medicine combination
            $table->string('dmdctr',30); //drugs and medicine name
            $table->string('loc_code',30); //location
            $table->string('chrgcode',30)->nullable(true); //type of account
            $table->string('exp_date')->nullable(true);
            $table->decimal('qty', 12, 2)->nullable(true)->default('0'); //issued qty by warehouse
            $table->decimal('returned_qty', 12, 2)->nullable(true)->default('0'); //issued qty by warehouse
            $table->string('status',30)->default('Issued')->nullable(true); //type of account
            $table->string('user_id',30)->nullable(true); //type of account
            $table->string('hpercode')->nullable();
            $table->string('enccode')->nullable();
            $table->string('toecode')->nullable();
            $table->string('pcchrgcod')->nullable();
            $table->decimal('pchrgup', 12, 2)->nullable()->default('0');
            $table->decimal('pcchrgamt', 12, 2)->nullable()->default('0');
            $table->decimal('sc_pwd', 12, 2)->nullable()->default('0');
            $table->decimal('ems', 12, 2)->nullable()->default('0');
            $table->decimal('maip', 12, 2)->nullable()->default('0');
            $table->decimal('wholesale', 12, 2)->nullable()->default('0');
            $table->decimal('pay', 12, 2)->nullable()->default('0');
            $table->decimal('medicare', 12, 2)->nullable()->default('0');
            $table->decimal('service', 12, 2)->nullable()->default('0');
            $table->decimal('govt_emp', 12, 2)->nullable()->default('0');
            $table->decimal('caf', 12, 2)->nullable()->default('0');
            $table->dateTime('dmdprdte'); //price date
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
        Schema::dropIfExists('pharm_drug_stock_issues');
    }
}
