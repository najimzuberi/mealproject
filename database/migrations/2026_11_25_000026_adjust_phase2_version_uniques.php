<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('strategic_plans', function (Blueprint $table) {
            $table->index('organization_id', 'strategic_plans_organization_id_index');
        });

        Schema::table('strategic_plans', function (Blueprint $table) {
            $table->dropUnique('strategic_plans_organization_id_code_unique');
            $table->unique(['organization_id', 'code', 'version'], 'strategic_plans_organization_id_code_version_unique');
        });

        Schema::table('annual_work_plans', function (Blueprint $table) {
            $table->index('organization_id', 'annual_work_plans_organization_id_index');
        });

        Schema::table('annual_work_plans', function (Blueprint $table) {
            $table->dropUnique('annual_work_plans_organization_id_code_unique');
            $table->unique(['organization_id', 'code', 'version'], 'annual_work_plans_organization_id_code_version_unique');
        });
    }

    public function down(): void
    {
        Schema::table('strategic_plans', function (Blueprint $table) {
            $table->dropUnique('strategic_plans_organization_id_code_version_unique');
            $table->unique(['organization_id', 'code'], 'strategic_plans_organization_id_code_unique');
            $table->dropIndex('strategic_plans_organization_id_index');
        });

        Schema::table('annual_work_plans', function (Blueprint $table) {
            $table->dropUnique('annual_work_plans_organization_id_code_version_unique');
            $table->unique(['organization_id', 'code'], 'annual_work_plans_organization_id_code_unique');
            $table->dropIndex('annual_work_plans_organization_id_index');
        });
    }
};
