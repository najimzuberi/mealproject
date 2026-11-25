<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->unique(['organization_id', 'code']);
            $table->index('status');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->unique(['organization_id', 'code']);
            $table->index('status');
        });

        Schema::table('reporting_periods', function (Blueprint $table) {
            $table->unique(['reporting_calendar_id', 'name']);
            $table->index(['organization_id', 'status']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });

        Schema::table('reporting_periods', function (Blueprint $table) {
            $table->dropUnique(['reporting_calendar_id', 'name']);
            $table->dropIndex(['organization_id', 'status']);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'code']);
            $table->dropIndex(['status']);
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'code']);
            $table->dropIndex(['status']);
        });
    }
};
