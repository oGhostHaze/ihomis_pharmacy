<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnderMaintenanceInPharmLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_locations', function (Blueprint $table) {
            $table->boolean('under_maintenance')->default(FALSE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_locations', function (Blueprint $table) {
            $table->dropColumn('under_maintenance');
        });
    }
}
