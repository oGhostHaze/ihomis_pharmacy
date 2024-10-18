<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugManualLogWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_manual_log_warehouses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('consumption_id')->nullable();
            $table->integer('loc_code');
            $table->string('dmdcomb', 30);
            $table->string('dmdctr', 30);
            $table->string('chrgcode', 30);
            $table->decimal('unit_cost', 12, 2)->nullable()->default(0);
            $table->decimal('unit_price', 12, 2)->nullable()->default(0);
            $table->decimal('beg_bal', 12, 2)->nullable()->default(0);
            $table->decimal('total_purchases', 12, 2)->nullable()->default(0);
            $table->decimal('sat_iss', 12, 2)->nullable()->default(0);
            $table->decimal('opd_iss', 12, 2)->nullable()->default(0);
            $table->decimal('cu_iss', 12, 2)->nullable()->default(0);
            $table->decimal('or_iss', 12, 2)->nullable()->default(0);
            $table->decimal('nst_iss', 12, 2)->nullable()->default(0);
            $table->decimal('others_iss', 12, 2)->nullable()->default(0);
            $table->decimal('returns_pullout', 12, 2)->nullable()->default(0);
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
        Schema::dropIfExists('pharm_drug_manual_log_warehouses');
    }
}