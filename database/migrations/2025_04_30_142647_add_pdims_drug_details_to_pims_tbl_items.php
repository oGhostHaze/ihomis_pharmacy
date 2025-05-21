<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         *  Schema::connection('pims')->table('tbl_items', function (Blueprint $table) {
         *     $table->string('pdims_itemcode', 50)->nullable()->after('description');
         *    $table->text('pdims_drugdesc')->nullable()->after('pdims_itemcode');
         *});
         */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pims')->table('tbl_items', function (Blueprint $table) {
            $table->dropColumn(['pdims_itemcode', 'pdims_drugdesc']);
        });
    }
};
