<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_assignments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_plan_item_id')->constrained('monitoring_plan_items')->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['monitoring_plan_item_id', 'reporting_period_id', 'assigned_to'],
                'rpt_assign_unique'
            );
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporting_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('monitoring_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_plan_item_id')->constrained('monitoring_plan_items')->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->decimal('numerator', 18, 4)->nullable();
            $table->decimal('denominator', 18, 4)->nullable();
            $table->decimal('value', 18, 4)->nullable();
            $table->json('disaggregations')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->string('dimension')->nullable();
            $table->string('label')->nullable();
            $table->decimal('value', 18, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name')->nullable();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dqa_assessments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('validity_score')->nullable();
            $table->unsignedTinyInteger('reliability_score')->nullable();
            $table->unsignedTinyInteger('completeness_score')->nullable();
            $table->unsignedTinyInteger('timeliness_score')->nullable();
            $table->unsignedTinyInteger('precision_score')->nullable();
            $table->unsignedTinyInteger('integrity_score')->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dqa_issues', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dqa_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dimension')->nullable();
            $table->string('severity')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dqa_issues');
        Schema::dropIfExists('dqa_assessments');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('submission_values');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('reporting_assignments');
    }
};
