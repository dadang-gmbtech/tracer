<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_page_contents', function (Blueprint $table) {
            // Off until an admin turns the "Laporan Tracer Studi" section on.
            $table->boolean('show_reports')->default(false)->after('alur_steps');
        });
    }

    public function down(): void
    {
        Schema::table('home_page_contents', function (Blueprint $table) {
            $table->dropColumn('show_reports');
        });
    }
};
