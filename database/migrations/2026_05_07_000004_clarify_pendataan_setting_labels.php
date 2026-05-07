<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'kop_baris_2')
            ->where('value', 'Sistem Pendaftaran Peserta Didik Baru (SPSB)')
            ->update(['value' => 'Sistem Pendataan Peserta Didik Baru (SPSB)']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'kop_baris_2')
            ->where('value', 'Sistem Pendataan Peserta Didik Baru (SPSB)')
            ->update(['value' => 'Sistem Pendaftaran Peserta Didik Baru (SPSB)']);
    }
};
