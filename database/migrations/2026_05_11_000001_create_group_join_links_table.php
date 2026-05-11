<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_join_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calon_santri_id')->constrained('calon_santris')->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->string('role', 20);
            $table->string('channel', 20);
            $table->string('group_type', 20);
            $table->text('target_url');
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->string('last_clicked_ip')->nullable();
            $table->text('last_clicked_user_agent')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['calon_santri_id', 'role', 'channel', 'group_type'], 'group_join_unique_recipient');
            $table->index(['calon_santri_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_join_links');
    }
};
