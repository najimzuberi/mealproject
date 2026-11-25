<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporting_periods', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->date('submission_deadline')->nullable()->after('ends_on');
            $table->timestamp('opened_at')->nullable()->after('status');
            $table->timestamp('closed_at')->nullable()->after('opened_at');
            $table->text('closure_notes')->nullable()->after('closed_at');
            $table->foreignId('responsible_officer_id')->nullable()->after('closure_notes')->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('reminder_days_before')->nullable()->after('responsible_officer_id');
        });

        Schema::create('reporting_reminders', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->constrained()->cascadeOnDelete();
            $table->string('reminder_type');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number');
            $table->string('title');
            $table->string('report_type');
            $table->string('status')->default('draft');
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('executive_summary')->nullable();
            $table->text('performance_summary')->nullable();
            $table->text('challenges')->nullable();
            $table->text('risks')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('ai_assisted')->default(false);
            $table->json('snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'reference_number']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('report_indicator_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reporting_period_id')->nullable()->constrained()->nullOnDelete();
            $table->string('indicator_code')->nullable();
            $table->string('indicator_name')->nullable();
            $table->decimal('baseline', 18, 4)->nullable();
            $table->decimal('target', 18, 4)->nullable();
            $table->decimal('actual', 18, 4)->nullable();
            $table->decimal('achievement_percent', 8, 2)->nullable();
            $table->decimal('variance', 18, 4)->nullable();
            $table->string('performance_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_indicator_results');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('reporting_reminders');

        Schema::table('reporting_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_officer_id');
            $table->dropColumn([
                'code',
                'submission_deadline',
                'opened_at',
                'closed_at',
                'closure_notes',
                'reminder_days_before',
            ]);
        });
    }
};
