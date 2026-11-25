<?php

namespace Database\Seeders;

use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Services\OrganizationBootstrapService;
use Illuminate\Database\Seeder;

/**
 * Defines org-scoped roles via Spatie teams (organization_id).
 * Run OrganizationBootstrapService::bootstrapRolesForOrganization() when an organization is registered.
 */
class RoleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $organization = Organization::query()->where('code', 'mealmaster-dev')->first();
        if ($organization === null) {
            return;
        }

        app(OrganizationBootstrapService::class)->bootstrapRolesForOrganization($organization);
    }
}
