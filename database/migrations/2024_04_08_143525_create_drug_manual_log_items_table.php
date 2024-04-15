<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugManualLogItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_manual_log_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('detail_id')->nullable();

            $table->integer('loc_code');
            $table->string('dmdcomb', 30);
            $table->string('dmdctr', 30);
            $table->string('chrgcode', 30);

            $table->decimal('unit_cost', 12, 2)->nullable()->default(0);
            $table->decimal('unit_price', 12, 2)->nullable()->default(0);

            $table->decimal('beg_bal', 12, 2)->nullable()->default(0);
            $table->decimal('delivered', 12, 2)->nullable()->default(0);

            $table->decimal('trans_in', 12, 2)->nullable()->default(0);
            $table->decimal('trans_out', 12, 2)->nullable()->default(0);

            $table->decimal('adjustments', 12, 2)->nullable()->default(0);

            $table->decimal('issue_qty', 12, 2)->nullable()->default(0);
            $table->decimal('return_qty', 12, 2)->nullable()->default(0);

            $table->decimal('ems', 12, 2)->nullable()->default(0);
            $table->decimal('maip', 12, 2)->nullable()->default(0);
            $table->decimal('wholesale', 12, 2)->nullable()->default(0);
            $table->decimal('pay', 12, 2)->nullable()->default(0);
            $table->decimal('service', 12, 2)->nullable()->default(0);
            $table->decimal('caf', 12, 2)->nullable()->default(0);
            $table->decimal('ris', 12, 2)->nullable()->default(0);
            $table->decimal('pcso', 12, 2)->nullable()->default(0);
            $table->decimal('phic', 12, 2)->nullable()->default(0);
            $table->decimal('konsulta', 12, 2)->nullable()->default(0);
            $table->decimal('opdpay', 12, 2)->nullable()->default(0);
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
        Schema::dropIfExists('pharm_drug_manual_log_items');
    }
}
