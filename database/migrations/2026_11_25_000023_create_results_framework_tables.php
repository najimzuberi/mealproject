<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results_frameworks', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('theory_of_change')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_framework_id')->nullable()->constrained('results_frameworks')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('results_framework_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('results')->nullOnDelete();
            $table->string('level');
            $table->string('code')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['results_framework_id', 'parent_id']);
        });

        Schema::create('result_assumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('result_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('likelihood')->nullable();
            $table->string('impact')->nullable();
            $table->text('mitigation')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_risks');
        Schema::dropIfExists('result_assumptions');
        Schema::dropIfExists('results');
        Schema::dropIfExists('results_frameworks');
    }
};
