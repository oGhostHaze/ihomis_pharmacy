<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrxo', function (Blueprint $table) {
            $table->string('original_enccode', 50)->nullable()->after('deptcode');
            $table->index('original_enccode', 'idx_hrxo_original_enccode');
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
            $table->dropIndex('idx_hrxo_original_enccode');
            $table->dropColumn('original_enccode');
        });
    }
};
