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
        Schema::table('tracer_responses', function (Blueprint $table) {
            // Negara tempat bekerja (raw code, e.g. "ID") — mirrors f5a1/f5a2's
            // raw-code storage. The form always renders this as a fixed,
            // disabled "Indonesia" field, so it's server-derived, not user input.
            $table->string('f5a0')->nullable()->after('f8');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracer_responses', function (Blueprint $table) {
            $table->dropColumn('f5a0');
        });
    }
};
