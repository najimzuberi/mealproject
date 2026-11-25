<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('definition')->nullable();
            $table->string('measurement_unit')->nullable();
            $table->string('numerator')->nullable();
            $table->string('denominator')->nullable();
            $table->string('formula')->nullable();
            $table->string('aggregation_method')->nullable();
            $table->string('direction_of_change')->nullable();
            $table->string('result_level')->nullable();
            $table->foreignId('strategic_objective_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('results_framework_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('result_id')->nullable()->constrained()->nullOnDelete();
            $table->string('indicator_type')->default('organizational');
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_indicator_id')->nullable()->constrained('indicators')->nullOnDelete();
            $table->string('source_url')->nullable();
            $table->string('source_organization')->nullable();
            $table->date('source_accessed_on')->nullable();
            $table->text('adaptation_notes')->nullable();
            $table->string('data_source')->nullable();
            $table->string('collection_method')->nullable();
            $table->string('collection_frequency')->nullable();
            $table->foreignId('responsible_collector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('me_validator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('means_of_verification')->nullable();
            $table->text('validation_rules')->nullable();
            $table->text('limitations')->nullable();
            $table->text('assumptions')->nullable();
            $table->text('quality_checks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code', 'version']);
        });

        Schema::create('indicator_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->date('baseline_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('indicator_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('target_period')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_cumulative')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('indicator_disaggregations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('dimension');
            $table->string('label')->nullable();
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_disaggregations');
        Schema::dropIfExists('indicator_targets');
        Schema::dropIfExists('indicator_baselines');
        Schema::dropIfExists('indicators');
    }
};
