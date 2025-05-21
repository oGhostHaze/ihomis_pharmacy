<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransferFieldsToRisTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         *  Schema::connection('pims')->table('tbl_ris', function (Blueprint $table) {
         *       $table->unsignedBigInteger('transferred_to_pdims')->nullable();
         *       $table->timestamp('transferred_at')->nullable();
         *   });
         *
         *  Schema::connection('pims')->table('tbl_ris_details', function (Blueprint $table) {
         *      $table->unsignedBigInteger('transferred_to_pdims')->nullable();
         *      $table->timestamp('transferred_at')->nullable();
        });
         */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('pims')->table('tbl_ris', function (Blueprint $table) {
            $table->dropColumn(['transferred_to_pdims', 'transferred_at']);
        });

        Schema::connection('pims')->table('tbl_ris_details', function (Blueprint $table) {
            $table->dropColumn(['transferred_to_pdims', 'transferred_at']);
        });
    }
}
