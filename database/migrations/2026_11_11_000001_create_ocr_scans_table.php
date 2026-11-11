<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_scans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('token_hash', 64)->unique();
            $table->char('session_hash', 64)->index();
            $table->string('target', 24);
            $table->string('status', 24)->default('queued')->index();
            $table->string('stage', 32)->default('queued');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('file_path');
            $table->string('mime_type', 100);
            $table->json('result')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_scans');
    }
};
