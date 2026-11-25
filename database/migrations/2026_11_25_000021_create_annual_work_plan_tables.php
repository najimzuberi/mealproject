<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_work_plans', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategic_plan_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->unsignedSmallInteger('planning_year');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_awp_id')->nullable()->constrained('annual_work_plans')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('awp_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annual_work_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategic_objective_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('awp_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('awp_priority_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('deliverable')->nullable();
            $table->decimal('budget_estimate', 15, 2)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status')->default('planned');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('awp_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('awp_activity_id')->constrained()->cascadeOnDelete();
            $table->string('quarter');
            $table->string('title');
            $table->date('due_on')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('awp_milestones');
        Schema::dropIfExists('awp_activities');
        Schema::dropIfExists('awp_priorities');
        Schema::dropIfExists('annual_work_plans');
    }
};
