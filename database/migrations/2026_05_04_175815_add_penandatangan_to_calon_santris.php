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
        Schema::table('calon_santris', function (Blueprint $table) {
            $table->string('penandatangan_nama')->nullable();
            $table->boolean('pernyataan_kebenaran_data')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_santris', function (Blueprint $table) {
            $table->dropColumn(['penandatangan_nama', 'pernyataan_kebenaran_data']);
        });
    }
};
