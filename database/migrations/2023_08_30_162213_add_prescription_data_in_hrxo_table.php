<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrescriptionDataInHrxoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrxo', function (Blueprint $table) {
            $table->string('prescription_data_id')->nullable();
            $table->string('prescribed_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hrxo', function (Blueprint $table) {
            $table->dropColumn('prescription_data_id', 'prescribed_by');
        });
    }
}
