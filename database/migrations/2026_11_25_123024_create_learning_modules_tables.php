<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->string('evaluation_type')->nullable();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->text('purpose')->nullable();
            $table->text('scope')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignId('evaluation_lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('evaluation_team')->nullable();
            $table->text('methodology')->nullable();
            $table->text('data_collection_methods')->nullable();
            $table->string('target_population')->nullable();
            $table->unsignedInteger('sample_size')->nullable();
            $table->string('geographic_coverage')->nullable();
            $table->text('tools')->nullable();
            $table->text('timeline_notes')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->string('criterion')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('evaluation_findings', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('supporting_evidence')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('result_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('indicator_id')->nullable()->constrained()->nullOnDelete();
            $table->text('conclusion')->nullable();
            $table->string('significance')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('evaluation_recommendations', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_finding_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->text('supporting_evidence')->nullable();
            $table->foreignId('responsible_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('management_response_status')->nullable();
            $table->text('management_response')->nullable();
            $table->date('response_date')->nullable();
            $table->foreignId('responsible_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('feedback_categories', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('feedback_cases', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number');
            $table->timestamp('submitted_at')->nullable();
            $table->string('feedback_type')->default('feedback');
            $table->foreignId('feedback_category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->string('submission_channel')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('geographic_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_anonymous')->default(false);
            $table->string('complainant_name')->nullable();
            $table->string('complainant_contact')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->string('confidentiality_level')->default('internal');
            $table->string('priority')->default('medium');
            $table->string('status')->default('received');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responsible_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('resolution_deadline')->nullable();
            $table->unsignedTinyInteger('escalation_level')->default(0);
            $table->text('escalation_reason')->nullable();
            $table->text('investigation_notes')->nullable();
            $table->text('resolution_description')->nullable();
            $table->text('action_taken')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feedback_loop_status')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'reference_number']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'confidentiality_level']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('evaluation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('feedback_case_id')->nullable()->constrained()->nullOnDelete();
            $table->text('context')->nullable();
            $table->text('challenge')->nullable();
            $table->text('what_worked')->nullable();
            $table->text('what_did_not_work')->nullable();
            $table->text('recommendation')->nullable();
            $table->foreignId('responsible_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('action_required')->nullable();
            $table->string('status')->default('identified');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'reference']);
        });

        Schema::create('lesson_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->morphs('linkable');
            $table->timestamps();

            $table->unique(['lesson_id', 'linkable_type', 'linkable_id'], 'lesson_link_unique');
        });

        Schema::create('learning_actions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->morphs('actionable');
            $table->text('description');
            $table->foreignId('responsible_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->text('required_evidence')->nullable();
            $table->string('status')->default('open');
            $table->date('completed_on')->nullable();
            $table->text('progress_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('document_type')->nullable();
            $table->json('tags')->nullable();
            $table->json('keywords')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('published_on')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('access_level')->default('internal');
            $table->string('status')->default('draft');
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('parent_document_id')->nullable()->constrained('knowledge_documents')->nullOnDelete();
            $table->text('ai_summary')->nullable();
            $table->boolean('ai_assisted')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code', 'version']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('learning_actions');
        Schema::dropIfExists('lesson_links');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('feedback_cases');
        Schema::dropIfExists('feedback_categories');
        Schema::dropIfExists('evaluation_recommendations');
        Schema::dropIfExists('evaluation_findings');
        Schema::dropIfExists('evaluation_questions');
        Schema::dropIfExists('evaluations');
    }
};
