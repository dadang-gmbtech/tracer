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
        Schema::table('alumni', function (Blueprint $table) {
            $table->index('graduation_year');
            // Unlike MySQL, Postgres does NOT auto-create an index for a
            // foreign key's referencing column — faculty_id/program_study_id
            // back nearly every role-scoped query in the app (dashboard,
            // exports, alumni listing) and had no index at all in production.
            $table->index('faculty_id');
            $table->index('program_study_id');
        });

        Schema::table('tracer_responses', function (Blueprint $table) {
            $table->index('f8');               // status: bekerja / wiraswasta / studi
            $table->index('submitted_at');     // monthly breakdown filter
            $table->index('work_province_id'); // province map grouping
        });

        Schema::table('employer_responses', function (Blueprint $table) {
            $table->index('alumni_id'); // same Postgres FK-index gap as above
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropIndex(['graduation_year']);
            $table->dropIndex(['faculty_id']);
            $table->dropIndex(['program_study_id']);
        });

        Schema::table('tracer_responses', function (Blueprint $table) {
            $table->dropIndex(['f8']);
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['work_province_id']);
        });

        Schema::table('employer_responses', function (Blueprint $table) {
            $table->dropIndex(['alumni_id']);
        });
    }
};
