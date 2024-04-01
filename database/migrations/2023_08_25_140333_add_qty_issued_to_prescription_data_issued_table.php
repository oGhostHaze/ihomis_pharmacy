<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQtyIssuedToPrescriptionDataIssuedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('webapp')->table('webapp.dbo.prescription_data_issued', function (Blueprint $table) {
            $table->decimal('qtyissued', 18, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('webapp')->table('webapp.dbo.prescription_data_issued', function (Blueprint $table) {
            $table->dropColumn('qtyissued');
        });
    }
}
