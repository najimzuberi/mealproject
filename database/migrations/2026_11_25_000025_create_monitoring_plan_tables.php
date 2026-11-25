<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_plans', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_monitoring_plan_id')->nullable()->constrained('monitoring_plans')->nullOnDelete();
            $table->text('intended_use')->nullable();
            $table->text('assumptions')->nullable();
            $table->text('limitations')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code', 'version']);
        });

        Schema::create('monitoring_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('baseline_value')->nullable();
            $table->string('target_value')->nullable();
            $table->string('data_source')->nullable();
            $table->string('collection_method')->nullable();
            $table->string('collection_frequency')->nullable();
            $table->date('collection_deadline')->nullable();
            $table->foreignId('responsible_collector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('me_validator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('means_of_verification')->nullable();
            $table->text('validation_rules')->nullable();
            $table->string('reporting_format')->nullable();
            $table->text('intended_use')->nullable();
            $table->text('assumptions')->nullable();
            $table->text('limitations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['monitoring_plan_id', 'indicator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_plan_items');
        Schema::dropIfExists('monitoring_plans');
    }
};
