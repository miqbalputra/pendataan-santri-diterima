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
            $table->string('nik_wali')->nullable();
            $table->string('tempat_lahir_wali')->nullable();
            $table->date('tanggal_lahir_wali')->nullable();
            $table->text('alamat_wali')->nullable();
            $table->string('rt_rw_wali')->nullable();
            $table->string('kelurahan_desa_wali')->nullable();
            $table->string('kecamatan_wali')->nullable();
            $table->string('status_tahsin_wali')->nullable()->default('Belum');
            $table->string('pengajar_tahsin_wali')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_santris', function (Blueprint $table) {
            $table->dropColumn([
                'nik_wali',
                'tempat_lahir_wali',
                'tanggal_lahir_wali',
                'alamat_wali',
                'rt_rw_wali',
                'kelurahan_desa_wali',
                'kecamatan_wali',
                'status_tahsin_wali',
                'pengajar_tahsin_wali'
            ]);
        });
    }
};
