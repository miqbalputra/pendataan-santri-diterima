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
            $table->renameColumn('email_orangtua', 'email_ayah');
            $table->string('email_ibu')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_santris', function (Blueprint $table) {
            $table->renameColumn('email_ayah', 'email_orangtua');
            $table->dropColumn('email_ibu');
        });
    }
};
