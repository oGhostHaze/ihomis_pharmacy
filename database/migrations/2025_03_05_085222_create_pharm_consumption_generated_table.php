<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmConsumptionGeneratedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_consumption_generated', function (Blueprint $table) {
            $table->id();
            $table->string('dmdcomb');
            $table->string('dmdctr');
            $table->string('loc_code');
            $table->string('drug_concat')->nullable();
            $table->decimal('purchased', 15, 2)->default(0);
            $table->decimal('received_iotrans', 15, 2)->default(0);
            $table->decimal('transferred_iotrans', 15, 2)->default(0);
            $table->decimal('beg_bal', 15, 2)->default(0);
            $table->decimal('ems', 15, 2)->default(0);
            $table->decimal('maip', 15, 2)->default(0);
            $table->decimal('wholesale', 15, 2)->default(0);
            $table->decimal('opdpay', 15, 2)->default(0);
            $table->decimal('pay', 15, 2)->default(0);
            $table->decimal('service', 15, 2)->default(0);
            $table->decimal('pullout_qty', 15, 2)->default(0);
            $table->decimal('konsulta', 15, 2)->default(0);
            $table->decimal('pcso', 15, 2)->default(0);
            $table->decimal('phic', 15, 2)->default(0);
            $table->decimal('caf', 15, 2)->default(0);
            $table->decimal('issue_qty', 15, 2)->default(0);
            $table->decimal('return_qty', 15, 2)->default(0);
            $table->decimal('acquisition_cost', 15, 2)->default(0);
            $table->decimal('dmselprice', 15, 2)->default(0);
            $table->unsignedBigInteger('consumption_id');
            $table->string('chrgcode');
            $table->timestamps();

            // Add indexes for faster querying
            $table->index(['consumption_id']);
            $table->index(['loc_code']);
            $table->index(['dmdcomb', 'dmdctr']);
            $table->index(['chrgcode']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pharm_consumption_generated');
    }
}
