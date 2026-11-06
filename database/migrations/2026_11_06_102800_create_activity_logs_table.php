<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // "admin" = everyone managed in Manajemen User; "alumni" = alumni
            // and Pengguna Alumni (who fill their form without logging in).
            $table->string('category', 20)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots, so a log stays readable after the user is renamed or deleted.
            $table->string('actor_name')->nullable();
            $table->string('actor_role')->nullable();
            $table->foreignId('impersonator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('alumni_id')->nullable()->constrained('alumni')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('description');
            $table->boolean('succeeded')->default(true);
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
