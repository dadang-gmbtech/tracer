<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            // Nullable: the existing "Luar Negeri" pseudo-province has no
            // real country, and pre-existing rows won't have one until the
            // new master-data import is run.
            $table->foreignId('country_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
        });
    }
};
