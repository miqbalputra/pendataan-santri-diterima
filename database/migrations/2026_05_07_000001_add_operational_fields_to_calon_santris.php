<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calon_santris', function (Blueprint $table) {
            if (!Schema::hasColumn('calon_santris', 'nomor_pendaftaran')) {
                $table->string('nomor_pendaftaran')->nullable()->unique()->after('id');
            }

            if (!Schema::hasColumn('calon_santris', 'periode_id')) {
                $table->foreignId('periode_id')->nullable()->after('nomor_pendaftaran')->constrained('periodes')->nullOnDelete();
            }

            if (!Schema::hasColumn('calon_santris', 'dokumen_status')) {
                $table->json('dokumen_status')->nullable()->after('status_pendaftaran');
            }

            if (!Schema::hasColumn('calon_santris', 'dokumen_catatan')) {
                $table->text('dokumen_catatan')->nullable()->after('dokumen_status');
            }

            if (!Schema::hasColumn('calon_santris', 'revisi_token')) {
                $table->string('revisi_token')->nullable()->unique()->after('dokumen_catatan');
            }

            if (!Schema::hasColumn('calon_santris', 'revisi_diminta_pada')) {
                $table->timestamp('revisi_diminta_pada')->nullable()->after('revisi_token');
            }

            if (!Schema::hasColumn('calon_santris', 'revisi_selesai_pada')) {
                $table->timestamp('revisi_selesai_pada')->nullable()->after('revisi_diminta_pada');
            }
        });

        DB::table('calon_santris')
            ->whereNull('nomor_pendaftaran')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function ($row) {
                $year = $row->created_at ? date('Y', strtotime($row->created_at)) : date('Y');
                DB::table('calon_santris')->where('id', $row->id)->update([
                    'nomor_pendaftaran' => 'SPSB-' . $year . '-' . str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                    'revisi_token' => Str::random(48),
                ]);
            });

        DB::table('calon_santris')
            ->whereNull('revisi_token')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($row) {
                DB::table('calon_santris')->where('id', $row->id)->update([
                    'revisi_token' => Str::random(48),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('calon_santris', function (Blueprint $table) {
            foreach (['periode_id', 'nomor_pendaftaran', 'dokumen_status', 'dokumen_catatan', 'revisi_token', 'revisi_diminta_pada', 'revisi_selesai_pada'] as $column) {
                if (Schema::hasColumn('calon_santris', $column)) {
                    if ($column === 'periode_id') {
                        $table->dropConstrainedForeignId('periode_id');
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
