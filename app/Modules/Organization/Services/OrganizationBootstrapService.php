<?php

namespace App\Modules\Organization\Services;

use App\Modules\AccessControl\Models\Role;
use App\Modules\AccessControl\Support\Permissions;
use App\Modules\Organization\Models\Organization;
use Spatie\Permission\Models\Permission;

class OrganizationBootstrapService
{
    /**
     * Role templates. Permissions use module.action naming.
     * Org membership roles are assigned via Spatie model_has_roles scoped by organization_id (teams).
     *
     * System administrators configure infrastructure/access but do not receive
     * programme-data approval authority for Phase 2 MEAL artefacts.
     *
     * @return array<string, list<string>>
     */
    public static function rolePermissionMap(): array
    {
        $viewStructure = [
            'organization.view',
            'departments.view',
            'offices.view',
            'facilities.view',
            'geography.view',
            'sectors.view',
        ];

        $programmeView = [
            'strategic_plans.view',
            'annual_work_plans.view',
            'projects.view',
            'results_frameworks.view',
            'indicators.view',
            'monitoring_plans.view',
        ];

        return [
            'system_administrator' => self::systemAdministratorPermissions(),
            'project_manager' => array_values(array_unique(array_merge(
                $viewStructure,
                $programmeView,
                [
                    'reporting_calendars.view',
                    'strategic_plans.create',
                    'strategic_plans.edit',
                    'strategic_plans.submit',
                    'strategic_plans.manage',
                    'annual_work_plans.create',
                    'annual_work_plans.edit',
                    'annual_work_plans.submit',
                    'annual_work_plans.manage',
                    'projects.create',
                    'projects.edit',
                    'projects.submit',
                    'projects.manage',
                    'results_frameworks.view',
                    'indicators.view',
                    'monitoring_plans.view',
                    'performance.view',
                    'reports.view',
                    'reports.create',
                    'reports.edit',
                    'reports.submit',
                    'reports.export',
                    'evaluations.view',
                    'feedback.view',
                    'feedback.create',
                    'feedback.edit',
                    'lessons.view',
                    'lessons.create',
                    'knowledge.view',
                    'knowledge.download',
                ],
            ))),
            'me_officer' => array_values(array_unique(array_merge(
                $viewStructure,
                $programmeView,
                [
                    'organization.view',
                    'reporting_calendars.view',
                    'reporting_calendars.manage',
                    'reporting_calendars.approve',
                    'strategic_plans.review',
                    'strategic_plans.approve',
                    'annual_work_plans.review',
                    'annual_work_plans.approve',
                    'projects.approve',
                    'results_frameworks.create',
                    'results_frameworks.edit',
                    'results_frameworks.submit',
                    'results_frameworks.review',
                    'results_frameworks.approve',
                    'results_frameworks.manage',
                    'indicators.create',
                    'indicators.edit',
                    'indicators.submit',
                    'indicators.review',
                    'indicators.approve',
                    'indicators.manage',
                    'monitoring_plans.create',
                    'monitoring_plans.edit',
                    'monitoring_plans.submit',
                    'monitoring_plans.review',
                    'monitoring_plans.approve',
                    'monitoring_plans.manage',
                    'submissions.view',
                    'submissions.create',
                    'submissions.edit',
                    'submissions.submit',
                    'submissions.verify',
                    'submissions.validate',
                    'submissions.approve',
                    'submissions.manage',
                    'dqa.view',
                    'dqa.create',
                    'dqa.edit',
                    'dqa.manage',
                    'performance.view',
                    'reports.view',
                    'reports.create',
                    'reports.edit',
                    'reports.submit',
                    'reports.approve',
                    'reports.export',
                    'reports.manage',
                    'evaluations.view',
                    'evaluations.create',
                    'evaluations.edit',
                    'evaluations.submit',
                    'evaluations.approve',
                    'evaluations.manage',
                    'feedback.view',
                    'feedback.create',
                    'feedback.edit',
                    'feedback.manage',
                    'feedback.sensitive',
                    'lessons.view',
                    'lessons.create',
                    'lessons.edit',
                    'lessons.submit',
                    'lessons.approve',
                    'lessons.manage',
                    'knowledge.view',
                    'knowledge.create',
                    'knowledge.edit',
                    'knowledge.download',
                    'knowledge.manage',
                ],
            ))),
            'field_officer' => array_values(array_unique(array_merge(
                [
                    'departments.view',
                    'offices.view',
                    'geography.view',
                    'reporting_calendars.view',
                    'submissions.view',
                    'submissions.create',
                    'submissions.edit',
                    'submissions.submit',
                ],
                $programmeView,
            ))),
            'verification_supervisor' => array_values(array_unique(array_merge(
                $viewStructure,
                $programmeView,
                [
                    'reporting_calendars.view',
                    'reporting_calendars.review',
                    'monitoring_plans.view',
                    'monitoring_plans.review',
                    'indicators.view',
                    'submissions.view',
                    'submissions.verify',
                    'dqa.view',
                    'dqa.create',
                    'dqa.edit',
                    'performance.view',
                    'reports.view',
                ],
            ))),
            'donor_officer' => array_values(array_unique(array_merge(
                [
                    'organization.view',
                    'reporting_calendars.view',
                    'submissions.view',
                    'dqa.view',
                    'performance.view',
                    'reports.view',
                    'reports.export',
                ],
                $programmeView,
            ))),
        ];
    }

    /**
     * @return list<string>
     */
    public static function systemAdministratorPermissions(): array
    {
        $programmeApprove = [
            'strategic_plans.approve',
            'annual_work_plans.approve',
            'projects.approve',
            'results_frameworks.approve',
            'indicators.approve',
            'monitoring_plans.approve',
        ];

        return array_values(array_filter(
            Permissions::ALL,
            static fn (string $permission): bool => ! in_array($permission, $programmeApprove, true),
        ));
    }

    /**
     * @return list<string>
     */
    public static function modules(): array
    {
        return [
            'organization',
            'users',
            'roles',
            'departments',
            'offices',
            'facilities',
            'geography',
            'sectors',
            'teams',
            'reporting_calendars',
            'audit',
            'settings',
            'strategic_plans',
            'annual_work_plans',
            'projects',
            'results_frameworks',
            'indicators',
            'monitoring_plans',
        ];
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return ['view', 'create', 'edit', 'submit', 'review', 'approve', 'publish', 'manage'];
    }

    /**
     * @return list<string>
     */
    public static function allViewPermissions(): array
    {
        $permissions = [];
        foreach (self::modules() as $module) {
            $permissions[] = $module.'.view';
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function systemAdministratorManagePermissions(): array
    {
        return [
            'organization.manage',
            'users.manage',
            'roles.manage',
            'departments.manage',
            'offices.manage',
            'facilities.manage',
            'geography.manage',
            'sectors.manage',
            'reporting_calendars.manage',
            'settings.manage',
            'audit.manage',
            'strategic_plans.manage',
            'annual_work_plans.manage',
            'projects.manage',
            'results_frameworks.manage',
            'indicators.manage',
            'monitoring_plans.manage',
        ];
    }

    public function bootstrapRolesForOrganization(Organization $organization): void
    {
        $maps = self::rolePermissionMap();

        $displayNames = [
            'system_administrator' => 'System Administrator',
            'project_manager' => 'Project Manager',
            'me_officer' => 'M&E Officer',
            'field_officer' => 'Field Officer',
            'verification_supervisor' => 'Field/Data Verification Supervisor',
            'donor_officer' => 'Donor Officer',
        ];

        foreach ($maps as $roleName => $permissionNames) {
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'organization_id' => $organization->id,
                ],
                [
                    'display_name' => $displayNames[$roleName] ?? $roleName,
                    'is_system' => true,
                ],
            );

            $role->fill([
                'display_name' => $displayNames[$roleName] ?? $roleName,
                'is_system' => true,
            ])->save();

            $permissions = Permission::query()
                ->whereIn('name', $permissionNames)
                ->where('guard_name', 'web')
                ->get();

            $role->syncPermissions($permissions);
        }
    }
}
