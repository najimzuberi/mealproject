<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('strategic_plans') && ! Schema::hasColumn('strategic_plans', 'strategic_context')) {
            Schema::table('strategic_plans', function (Blueprint $table) {
                $table->text('strategic_context')->nullable()->after('mission');
                $table->json('core_values')->nullable()->after('strategic_context');
                $table->foreignId('owner_user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->after('owner_user_id')->constrained('departments')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable()->after('approved_at');
            });
        }

        if (! Schema::hasTable('strategic_plan_priorities')) {
            Schema::create('strategic_plan_priorities', function (Blueprint $table) {
                $table->id();
                $table->ulid('ulid')->unique();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('strategic_plan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('code')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->ulid('ulid')->unique();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->morphs('documentable');
                $table->string('title')->nullable();
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('strategic_goals') && ! Schema::hasColumn('strategic_goals', 'category')) {
            Schema::table('strategic_goals', function (Blueprint $table) {
                $table->string('category')->nullable()->after('title');
                $table->unsignedInteger('priority')->default(0)->after('category');
                $table->foreignId('owner_user_id')->nullable()->after('priority')->constrained('users')->nullOnDelete();
                $table->date('start_date')->nullable()->after('expected_result');
                $table->date('end_date')->nullable()->after('start_date');
                $table->string('status')->default('active')->after('end_date');
            });
        }

        if (Schema::hasTable('strategic_objectives') && ! Schema::hasColumn('strategic_objectives', 'priority')) {
            Schema::table('strategic_objectives', function (Blueprint $table) {
                $table->unsignedInteger('priority')->default(0)->after('title');
                $table->date('start_date')->nullable()->after('expected_result');
                $table->date('end_date')->nullable()->after('start_date');
                $table->string('status')->default('active')->after('end_date');
            });
        }

        if (Schema::hasTable('key_result_areas') && ! Schema::hasColumn('key_result_areas', 'department_id')) {
            Schema::table('key_result_areas', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('strategic_objective_id')->constrained()->nullOnDelete();
                $table->unsignedInteger('priority')->default(0)->after('description');
                $table->string('status')->default('active')->after('priority');
            });
        }

        if (! Schema::hasTable('strategic_outcomes')) {
            Schema::create('strategic_outcomes', function (Blueprint $table) {
                $table->id();
                $table->ulid('ulid')->unique();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('strategic_plan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('strategic_objective_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('code')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->text('expected_change')->nullable();
                $table->string('target_period')->nullable();
                $table->string('status')->default('active');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('strategic_indicators')) {
            Schema::create('strategic_indicators', function (Blueprint $table) {
                $table->id();
                $table->ulid('ulid')->unique();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('strategic_plan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('strategic_objective_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('strategic_outcome_id')->nullable()->constrained('strategic_outcomes')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('indicator_id')->nullable()->constrained('indicators')->nullOnDelete();
                $table->string('code')->nullable();
                $table->string('name');
                $table->text('definition')->nullable();
                $table->string('measurement_unit')->nullable();
                $table->string('measurement_direction')->nullable();
                $table->decimal('baseline_value', 18, 4)->nullable();
                $table->date('baseline_date')->nullable();
                $table->decimal('target_value', 18, 4)->nullable();
                $table->string('target_period')->nullable();
                $table->string('data_source')->nullable();
                $table->string('measurement_frequency')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('annual_work_plans') && ! Schema::hasColumn('annual_work_plans', 'owner_user_id')) {
            Schema::table('annual_work_plans', function (Blueprint $table) {
                $table->foreignId('owner_user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->after('owner_user_id')->constrained('departments')->nullOnDelete();
                $table->date('start_date')->nullable()->after('planning_year');
                $table->date('end_date')->nullable()->after('start_date');
                $table->string('currency', 10)->nullable()->after('end_date');
                $table->decimal('total_budget', 18, 2)->default(0)->after('currency');
                $table->timestamp('submitted_at')->nullable()->after('approved_at');
            });
        }

        if (Schema::hasTable('awp_priorities') && ! Schema::hasColumn('awp_priorities', 'code')) {
            Schema::table('awp_priorities', function (Blueprint $table) {
                $table->string('code')->nullable()->after('strategic_objective_id');
                $table->foreignId('department_id')->nullable()->after('code')->constrained()->nullOnDelete();
                $table->unsignedInteger('priority_level')->default(0)->after('description');
                $table->string('status')->default('active')->after('priority_level');
            });
        }

        if (Schema::hasTable('awp_activities') && ! Schema::hasColumn('awp_activities', 'annual_work_plan_id')) {
            Schema::table('awp_activities', function (Blueprint $table) {
                $table->foreignId('annual_work_plan_id')->nullable()->after('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->nullable()->after('responsible_user_id')->constrained('teams')->nullOnDelete();
                $table->foreignId('geographic_unit_id')->nullable()->after('team_id')->constrained()->nullOnDelete();
                $table->foreignId('strategic_outcome_id')->nullable()->after('geographic_unit_id')->constrained('strategic_outcomes')->nullOnDelete();
                $table->string('code')->nullable()->after('awp_priority_id');
                $table->string('quarter')->nullable()->after('ends_on');
                $table->string('funding_source')->nullable()->after('budget_estimate');
                $table->text('expected_output')->nullable()->after('deliverable');
            });
        }

        if (! Schema::hasTable('activity_objectives')) {
            Schema::create('activity_objectives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('awp_activity_id')->constrained('awp_activities')->cascadeOnDelete();
                $table->foreignId('strategic_objective_id')->constrained()->cascadeOnDelete();
                $table->unique(['awp_activity_id', 'strategic_objective_id'], 'activity_obj_unique');
            });
        } elseif (! $this->hasIndex('activity_objectives', 'activity_obj_unique')) {
            Schema::table('activity_objectives', function (Blueprint $table) {
                $table->unique(['awp_activity_id', 'strategic_objective_id'], 'activity_obj_unique');
            });
        }

        if (! Schema::hasTable('awp_budget_lines')) {
            Schema::create('awp_budget_lines', function (Blueprint $table) {
                $table->id();
                $table->ulid('ulid')->unique();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('annual_work_plan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('awp_activity_id')->constrained('awp_activities')->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->string('code')->nullable();
                $table->string('category')->nullable();
                $table->text('description')->nullable();
                $table->decimal('amount', 18, 2);
                $table->string('currency', 10)->default('USD');
                $table->string('funding_source')->nullable();
                $table->unsignedSmallInteger('financial_year')->nullable();
                $table->string('quarter')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('awp_milestones_plan')) {
            Schema::create('awp_milestones_plan', function (Blueprint $table) {
                $table->id();
                $table->ulid('ulid')->unique();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('annual_work_plan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('strategic_objective_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
                $table->string('code')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('quarter');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->text('expected_result')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('awp_milestones_plan');
        Schema::dropIfExists('awp_budget_lines');
        Schema::dropIfExists('activity_objectives');
        Schema::dropIfExists('strategic_indicators');
        Schema::dropIfExists('strategic_outcomes');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('strategic_plan_priorities');
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
