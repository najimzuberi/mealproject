<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->text('objectives')->nullable()->after('description');
            $table->text('strategic_alignment')->nullable()->after('objectives');
            $table->foreignId('sector_id')->nullable()->after('strategic_alignment')->constrained('sectors')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('sector_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('programme_manager_id')->nullable()->after('department_id')->constrained('users')->nullOnDelete();
            $table->foreignId('strategic_plan_id')->nullable()->after('programme_manager_id')->constrained('strategic_plans')->nullOnDelete();
            $table->date('starts_on')->nullable()->after('strategic_plan_id');
            $table->date('ends_on')->nullable()->after('starts_on');
            $table->decimal('budget_envelope', 18, 2)->nullable()->after('ends_on');
            $table->string('currency', 10)->nullable()->after('budget_envelope');
            $table->string('donor_name')->nullable()->after('currency');
            $table->text('donor_information')->nullable()->after('donor_name');
            $table->json('partners')->nullable()->after('donor_information');
            $table->json('target_groups')->nullable()->after('partners');
            $table->string('thematic_area')->nullable()->after('target_groups');
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        Schema::create('programme_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_label')->nullable();
            $table->unique(['programme_id', 'user_id']);
        });

        Schema::create('programme_geographic_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('geographic_unit_id')->constrained()->cascadeOnDelete();
            $table->unique(['programme_id', 'geographic_unit_id'], 'programme_geo_unique');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->text('background')->nullable()->after('description');
            $table->text('problem_statement')->nullable()->after('background');
            $table->json('target_groups')->nullable()->after('problem_statement');
            $table->string('currency', 10)->nullable()->after('budget');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('stakeholders', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->morphs('stakeholderable');
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('role')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('results_frameworks', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('version');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('results_frameworks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['submitted_at', 'approved_at']);
        });

        Schema::dropIfExists('stakeholders');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['background', 'problem_statement', 'target_groups', 'currency', 'submitted_at', 'approved_at']);
        });

        Schema::dropIfExists('programme_geographic_unit');
        Schema::dropIfExists('programme_user');

        Schema::table('programmes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sector_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('programme_manager_id');
            $table->dropConstrainedForeignId('strategic_plan_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'objectives',
                'strategic_alignment',
                'starts_on',
                'ends_on',
                'budget_envelope',
                'currency',
                'donor_name',
                'donor_information',
                'partners',
                'target_groups',
                'thematic_area',
            ]);
        });
    }
};
