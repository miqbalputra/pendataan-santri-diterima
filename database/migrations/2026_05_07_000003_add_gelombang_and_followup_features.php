<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gelombangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gelombang');
            $table->unsignedInteger('kuota')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::table('calon_santris', function (Blueprint $table) {
            if (!Schema::hasColumn('calon_santris', 'gelombang_id')) {
                $table->foreignId('gelombang_id')->nullable()->after('periode_id')->constrained('gelombangs')->nullOnDelete();
            }

            if (!Schema::hasColumn('calon_santris', 'followup_sudah_masuk_grup')) {
                $table->boolean('followup_sudah_masuk_grup')->default(false)->after('revisi_selesai_pada');
            }

            if (!Schema::hasColumn('calon_santris', 'followup_sudah_dihubungi')) {
                $table->boolean('followup_sudah_dihubungi')->default(false)->after('followup_sudah_masuk_grup');
            }

            if (!Schema::hasColumn('calon_santris', 'followup_catatan')) {
                $table->text('followup_catatan')->nullable()->after('followup_sudah_dihubungi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calon_santris', function (Blueprint $table) {
            if (Schema::hasColumn('calon_santris', 'gelombang_id')) {
                $table->dropConstrainedForeignId('gelombang_id');
            }

            foreach (['followup_sudah_masuk_grup', 'followup_sudah_dihubungi', 'followup_catatan'] as $column) {
                if (Schema::hasColumn('calon_santris', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('gelombangs');
    }
};
