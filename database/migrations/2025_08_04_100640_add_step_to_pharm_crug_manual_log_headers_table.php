<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStepToPharmCrugManualLogHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_manual_log_headers', function (Blueprint $table) {
            $table->integer('step')->default(0)->nullable()->after('generated_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_drug_manual_log_headers', function (Blueprint $table) {
            $table->dropColumn('step');
        });
    }
}