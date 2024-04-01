<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrescriptionDataInHrxoissueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrxoissue', function (Blueprint $table) {
            $table->boolean('ris')->nullable()->default(false);
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
        Schema::table('hrxoissue', function (Blueprint $table) {
            $table->dropColumn('ris', 'prescription_data_id', 'prescribed_by');
        });
    }
}
