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
            $table->string('no_seri_ijazah')->nullable();
            $table->string('no_seri_skhun')->nullable();
            $table->string('no_ujian_nasional')->nullable();
            $table->string('nik', 20)->unique();
            $table->string('nama_sekolah_asal')->nullable();
            $table->string('npsn_sekolah_asal')->nullable();
            $table->text('alamat_sekolah_asal')->nullable();
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('agama')->nullable();
            $table->string('berkebutuhan_khusus')->nullable();
            
            // --- ALAMAT ---
            $table->text('alamat_lengkap');
            $table->string('dusun')->nullable();
            $table->string('rt_rw')->nullable();
            $table->string('kelurahan_desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('propinsi')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('alat_transportasi')->nullable();
            $table->string('jenis_tinggal')->nullable();
            $table->string('no_telepon_rumah')->nullable();
            $table->string('email')->nullable();
            $table->string('hobi')->nullable();
            
            // --- DATA AYAH ---
            $table->string('nama_ayah');
            $table->string('nik_ayah', 20)->nullable();
            $table->string('tempat_lahir_ayah')->nullable();
            $table->date('tanggal_lahir_ayah')->nullable();
            $table->string('berkebutuhan_khusus_ayah')->nullable();
            $table->string('pekerjaan_ayah')->nullable();
            $table->string('pendidikan_ayah')->nullable();
            $table->string('no_wa_ayah')->nullable();
            $table->string('penghasilan_ayah')->nullable();
            $table->text('alamat_ayah')->nullable();
            $table->string('rt_rw_ayah')->nullable();
            $table->string('kelurahan_desa_ayah')->nullable();
            $table->string('kecamatan_ayah')->nullable();
            $table->enum('status_tahsin_ayah', ['Sudah', 'Belum'])->default('Belum');
            $table->string('pengajar_tahsin_ayah')->nullable();

            // --- DATA IBU ---
            $table->string('nama_ibu');
            $table->string('nik_ibu', 20)->nullable();
            $table->string('tempat_lahir_ibu')->nullable();
            $table->date('tanggal_lahir_ibu')->nullable();
            $table->string('berkebutuhan_khusus_ibu')->nullable();
            $table->string('pekerjaan_ibu')->nullable();
            $table->string('pendidikan_ibu')->nullable();
            $table->string('no_wa_ibu')->nullable();
            $table->string('penghasilan_ibu')->nullable();
            $table->text('alamat_ibu')->nullable();
            $table->string('rt_rw_ibu')->nullable();
            $table->string('kelurahan_desa_ibu')->nullable();
            $table->string('kecamatan_ibu')->nullable();
            $table->enum('status_tahsin_ibu', ['Sudah', 'Belum'])->default('Belum');
            $table->string('pengajar_tahsin_ibu')->nullable();

            // --- DATA WALI ---
            $table->string('nama_wali')->nullable();
            $table->string('tahun_lahir_wali')->nullable();
            $table->string('berkebutuhan_khusus_wali')->nullable();
            $table->string('pekerjaan_wali')->nullable();
            $table->string('pendidikan_wali')->nullable();
            $table->string('no_wa_wali')->nullable();
            $table->string('penghasilan_wali')->nullable();

            // --- DATA PERIODIK ---
            $table->string('tinggi_badan')->nullable();
            $table->string('berat_badan')->nullable();
            $table->string('jarak_ke_sekolah')->nullable();
            $table->string('waktu_tempuh')->nullable();
            $table->string('jumlah_saudara_kandung')->nullable();

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
