<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_plans', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_plan_id')->nullable()->constrained('strategic_plans')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('strategic_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategic_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('strategic_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategic_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategic_theme_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('expected_result')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('strategic_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategic_goal_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('expected_result')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('key_result_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('strategic_objective_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('strategic_goal_department', function (Blueprint $table) {
            $table->foreignId('goal_id')->constrained('strategic_goals')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            $table->unique(['goal_id', 'department_id']);
        });

        Schema::create('strategic_objective_department', function (Blueprint $table) {
            $table->foreignId('objective_id')->constrained('strategic_objectives')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            $table->unique(['objective_id', 'department_id']);
        });

        Schema::create('strategic_objective_user', function (Blueprint $table) {
            $table->foreignId('objective_id')->constrained('strategic_objectives')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(['objective_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_objective_user');
        Schema::dropIfExists('strategic_objective_department');
        Schema::dropIfExists('strategic_goal_department');
        Schema::dropIfExists('key_result_areas');
        Schema::dropIfExists('strategic_objectives');
        Schema::dropIfExists('strategic_goals');
        Schema::dropIfExists('strategic_themes');
        Schema::dropIfExists('strategic_plans');
    }
};
