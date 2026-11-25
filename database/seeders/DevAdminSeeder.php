<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Services\OrganizationBootstrapService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dev admin: admin@mealmaster.local / ChangeMeNow!123 — change before production.
 */
class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(
            ['code' => 'mealmaster-dev'],
            [
                'name' => 'Meal Master Dev',
                'type' => 'ngo',
                'status' => 'active',
                'email' => 'admin@mealmaster.local',
                'country' => 'TZ',
            ],
        );

        app(OrganizationBootstrapService::class)->bootstrapRolesForOrganization($organization);

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@mealmaster.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('ChangeMeNow!123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $organization->users()->syncWithoutDetaching([
            $user->id => [
                'status' => 'active',
                'is_default' => true,
                'joined_at' => now(),
            ],
        ]);

        setPermissionsTeamId($organization->id);
        $user->assignRole('system_administrator');
    }
}
