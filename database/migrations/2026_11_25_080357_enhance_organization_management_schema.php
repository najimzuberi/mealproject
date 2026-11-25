<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('headquarters_region')->nullable()->after('country');
            $table->text('physical_address')->nullable()->after('headquarters_region');
            $table->text('postal_address')->nullable()->after('physical_address');
            $table->string('timezone')->nullable()->after('postal_address');
            $table->string('default_language', 20)->nullable()->after('timezone');
            $table->string('default_currency', 10)->nullable()->after('default_language');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('organization_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('head_user_id')->nullable()->after('parent_id')->constrained('users')->nullOnDelete();
            $table->string('email')->nullable()->after('description');
            $table->string('phone', 50)->nullable()->after('email');
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('department_id')->constrained('offices')->nullOnDelete();
            $table->foreignId('geographic_unit_id')->nullable()->after('parent_id')->constrained('geographic_units')->nullOnDelete();
            $table->foreignId('manager_user_id')->nullable()->after('geographic_unit_id')->constrained('users')->nullOnDelete();
            $table->string('type')->nullable()->after('code');
            $table->text('description')->nullable()->after('type');
            $table->string('country')->nullable()->after('address');
            $table->string('region')->nullable()->after('country');
            $table->string('district')->nullable()->after('region');
            $table->string('contact_person')->nullable()->after('district');
            $table->string('email')->nullable()->after('contact_person');
            $table->string('phone', 50)->nullable()->after('email');
        });

        Schema::create('facility_types', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->foreignId('facility_type_id')->nullable()->after('office_id')->constrained('facility_types')->nullOnDelete();
            $table->foreignId('geographic_unit_id')->nullable()->after('facility_type_id')->constrained('geographic_units')->nullOnDelete();
            $table->text('description')->nullable()->after('type');
            $table->string('registration_number')->nullable()->after('description');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('contact_person')->nullable()->after('longitude');
            $table->string('contact_phone', 50)->nullable()->after('contact_person');
        });

        Schema::create('geographic_types', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedTinyInteger('default_level')->default(0);
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
        });

        Schema::table('geographic_units', function (Blueprint $table) {
            $table->foreignId('geographic_type_id')->nullable()->after('parent_id')->constrained('geographic_types')->nullOnDelete();
            $table->text('description')->nullable()->after('type');
            $table->decimal('latitude', 10, 7)->nullable()->after('description');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('leader_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');

        Schema::table('geographic_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('geographic_type_id');
            $table->dropColumn(['description', 'latitude', 'longitude']);
        });

        Schema::dropIfExists('geographic_types');

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_type_id');
            $table->dropConstrainedForeignId('geographic_unit_id');
            $table->dropColumn([
                'description',
                'registration_number',
                'latitude',
                'longitude',
                'contact_person',
                'contact_phone',
            ]);
        });

        Schema::dropIfExists('facility_types');

        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('geographic_unit_id');
            $table->dropConstrainedForeignId('manager_user_id');
            $table->dropColumn([
                'type',
                'description',
                'country',
                'region',
                'district',
                'contact_person',
                'email',
                'phone',
            ]);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('head_user_id');
            $table->dropColumn(['email', 'phone']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'headquarters_region',
                'physical_address',
                'postal_address',
                'timezone',
                'default_language',
                'default_currency',
            ]);
        });
    }
};
