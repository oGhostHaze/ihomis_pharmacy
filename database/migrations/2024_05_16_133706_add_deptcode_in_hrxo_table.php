<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeptcodeInHrxoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrxo', function (Blueprint $table) {
            $table->string('deptcode', 20)->nullable();
            $table->index('deptcode', 'idx_deptcode');
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
            $table->dropIndex('idx_deptcode');
            $table->dropColumn('deptcode');
        });
    }
}