<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calon_santris', function (Blueprint $table) {
            $table->id();
            
            // --- IDENTITAS SANTRI ---
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('nisn')->nullable();
            $table->string('nik', 20)->unique();
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            
            // --- ALAMAT ---
            $table->text('alamat_lengkap');
            $table->string('kelurahan_desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten_kota')->nullable();
            
            // --- DATA AYAH ---
            $table->string('nama_ayah');
            $table->string('pekerjaan_ayah')->nullable();
            $table->string('no_wa_ayah')->nullable();
            $table->boolean('is_ayah_tahsin')->default(false);

            // --- DATA IBU ---
            $table->string('nama_ibu');
            $table->string('pekerjaan_ibu')->nullable();
            $table->string('no_wa_ibu')->nullable();
            $table->boolean('is_ibu_tahsin')->default(false);

            // --- DATA SAUDARA ---
            $table->boolean('punya_saudara_di_sini')->default(false);
            $table->json('siblings_data')->nullable();

            // --- FILE DOKUMEN ---
            $table->string('foto_ktp_ayah')->nullable();
            $table->string('foto_ktp_ibu')->nullable();
            $table->string('foto_akta_anak')->nullable();
            $table->string('foto_kk')->nullable();

            $table->enum('status_pendaftaran', ['Pending', 'Diterima', 'Ditolak'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calon_santris');
    }
};
