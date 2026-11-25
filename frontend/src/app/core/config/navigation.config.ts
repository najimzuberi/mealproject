export type NavAvailability = 'live' | 'coming_soon';

export interface NavItem {
  id: string;
  label: string;
  icon: string;
  route?: string;
  permission?: string | string[];
  availability?: NavAvailability;
}

export interface NavGroup {
  id: string;
  label: string;
  icon: string;
  items: NavItem[];
}

/**
 * Configuration-driven sidebar. Mark availability as coming_soon until feature UIs ship.
 * Permission keys mirror Laravel Spatie permissions.
 */
export const NAVIGATION_CONFIG: NavGroup[] = [
  {
    id: 'overview',
    label: 'Overview',
    icon: 'dashboard',
    items: [
      {
        id: 'dashboard',
        label: 'Dashboard',
        icon: 'space_dashboard',
        route: '/app/dashboard',
        availability: 'live',
      },
    ],
  },
  {
    id: 'organization',
    label: 'Organization',
    icon: 'corporate_fare',
    items: [
      {
        id: 'org-profile',
        label: 'Organization Profile',
        icon: 'apartment',
        route: '/app/organization/profile',
        permission: 'organization.view',
        availability: 'live',
      },
      {
        id: 'departments',
        label: 'Departments',
        icon: 'account_tree',
        route: '/app/organization/departments',
        permission: 'departments.view',
        availability: 'live',
      },
      {
        id: 'offices',
        label: 'Offices',
        icon: 'business',
        route: '/app/organization/offices',
        permission: 'offices.view',
        availability: 'live',
      },
      {
        id: 'facilities',
        label: 'Facilities',
        icon: 'local_hospital',
        route: '/app/organization/facilities',
        permission: 'facilities.view',
        availability: 'live',
      },
      {
        id: 'geography',
        label: 'Geographic Structure',
        icon: 'map',
        route: '/app/organization/geography',
        permission: 'geography.view',
        availability: 'live',
      },
      {
        id: 'sectors',
        label: 'Sectors',
        icon: 'category',
        route: '/app/organization/sectors',
        permission: 'sectors.view',
        availability: 'live',
      },
      {
        id: 'users-teams',
        label: 'Users & Teams',
        icon: 'groups',
        route: '/app/organization/users-teams',
        permission: 'users.view',
        availability: 'live',
      },
      {
        id: 'reporting-calendars',
        label: 'Reporting Calendars',
        icon: 'calendar_month',
        route: '/app/organization/reporting-calendars',
        permission: 'reporting_calendars.view',
        availability: 'live',
      },
      {
        id: 'org-settings',
        label: 'Organization Settings',
        icon: 'tune',
        route: '/app/organization/settings',
        permission: 'settings.view',
        availability: 'live',
      },
    ],
  },
  {
    id: 'strategic',
    label: 'Strategy',
    icon: 'flag',
    items: [
      {
        id: 'strategic-plans',
        label: 'Strategic Plans',
        icon: 'flag',
        route: '/app/strategy/strategic-plans',
        permission: 'strategic_plans.view',
        availability: 'live',
      },
      {
        id: 'goals',
        label: 'Goals & Objectives',
        icon: 'track_changes',
        route: '/app/strategy/goals',
        permission: 'strategic_plans.view',
        availability: 'live',
      },
      {
        id: 'awp',
        label: 'Annual Work Plans',
        icon: 'event_note',
        route: '/app/strategy/annual-work-plans',
        permission: 'annual_work_plans.view',
        availability: 'live',
      },
    ],
  },
  {
    id: 'programme',
    label: 'Programmes',
    icon: 'work',
    items: [
      {
        id: 'programmes',
        label: 'Programmes',
        icon: 'folder_special',
        route: '/app/programme/programmes',
        permission: 'projects.view',
        availability: 'live',
      },
      {
        id: 'projects',
        label: 'Projects',
        icon: 'work',
        route: '/app/programme/projects',
        permission: 'projects.view',
        availability: 'live',
      },
      {
        id: 'results',
        label: 'Results Framework',
        icon: 'account_tree',
        route: '/app/programme/results-frameworks',
        permission: 'results_frameworks.view',
        availability: 'live',
      },
    ],
  },
  {
    id: 'meal',
    label: 'MEAL',
    icon: 'analytics',
    items: [
      {
        id: 'indicators',
        label: 'Indicator Library',
        icon: 'insights',
        route: '/app/meal/indicators',
        permission: 'indicators.view',
        availability: 'live',
      },
      {
        id: 'monitoring-plans',
        label: 'Monitoring Plans',
        icon: 'assignment',
        route: '/app/meal/monitoring-plans',
        permission: 'monitoring_plans.view',
        availability: 'live',
      },
      {
        id: 'data-collection',
        label: 'Data Collection',
        icon: 'edit_note',
        route: '/app/meal/data-collection',
        permission: ['submissions.view', 'monitoring_plans.view'],
        availability: 'live',
      },
      {
        id: 'dqa',
        label: 'Data Quality Assessment',
        icon: 'verified',
        route: '/app/meal/dqa',
        permission: ['dqa.view', 'monitoring_plans.view'],
        availability: 'live',
      },
    ],
  },
  {
    id: 'performance',
    label: 'Performance',
    icon: 'monitoring',
    items: [
      {
        id: 'perf-dashboard',
        label: 'Performance Dashboard',
        icon: 'monitoring',
        route: '/app/performance/dashboard',
        permission: 'indicators.view',
        availability: 'live',
      },
      {
        id: 'reports',
        label: 'Reports',
        icon: 'description',
        route: '/app/performance/reports',
        permission: ['reports.view', 'reporting_calendars.view'],
        availability: 'live',
      },
      {
        id: 'reporting-calendar',
        label: 'Reporting Calendar',
        icon: 'calendar_month',
        route: '/app/performance/reporting-calendar',
        permission: 'reporting_calendars.view',
        availability: 'live',
      },
    ],
  },
  {
    id: 'learning',
    label: 'Learning',
    icon: 'school',
    items: [
      {
        id: 'evaluations',
        label: 'Evaluations',
        icon: 'rate_review',
        route: '/app/learning/evaluations',
        permission: ['evaluations.view', 'evaluations.manage'],
        availability: 'live',
      },
      {
        id: 'feedback',
        label: 'Feedback & Complaints',
        icon: 'forum',
        route: '/app/learning/feedback',
        permission: ['feedback.view', 'feedback.manage'],
        availability: 'live',
      },
      {
        id: 'lessons',
        label: 'Lessons Learned',
        icon: 'lightbulb',
        route: '/app/learning/lessons',
        permission: ['lessons.view', 'lessons.manage'],
        availability: 'live',
      },
      {
        id: 'knowledge',
        label: 'Knowledge Repository',
        icon: 'menu_book',
        route: '/app/learning/knowledge',
        permission: ['knowledge.view', 'knowledge.manage'],
        availability: 'live',
      },
    ],
  },
  {
    id: 'admin',
    label: 'Administration',
    icon: 'admin_panel_settings',
    items: [
      { id: 'admin-users', label: 'Users', icon: 'person', permission: 'users.view', availability: 'coming_soon' },
      { id: 'roles', label: 'Roles & Permissions', icon: 'security', permission: 'roles.view', availability: 'coming_soon' },
      { id: 'settings', label: 'System Settings', icon: 'settings', route: '/app/organization/settings', permission: 'settings.view', availability: 'live' },
      { id: 'audit', label: 'Audit Logs', icon: 'history', permission: 'audit.view', availability: 'coming_soon' },
    ],
  },
];
