import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./layout/auth-layout/auth-layout').then((m) => m.AuthLayoutComponent),
    children: [
      {
        path: '',
        loadComponent: () => import('./features/auth/login/login').then((m) => m.LoginComponent),
        data: { breadcrumb: 'Sign in' },
      },
    ],
  },
  {
    path: 'app',
    canActivate: [authGuard],
    loadComponent: () => import('./layout/main-layout/main-layout').then((m) => m.MainLayoutComponent),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      {
        path: 'dashboard',
        loadComponent: () =>
          import('./features/dashboard/dashboard-overview/dashboard-overview').then(
            (m) => m.DashboardOverviewComponent,
          ),
        data: { breadcrumb: 'Dashboard' },
      },
      {
        path: 'organization/profile',
        loadComponent: () =>
          import('./features/organization/organization-profile/organization-profile').then(
            (m) => m.OrganizationProfileComponent,
          ),
        data: { breadcrumb: 'Organization Profile' },
      },
      {
        path: 'organization/departments',
        loadComponent: () =>
          import('./features/organization/departments/departments-page').then(
            (m) => m.DepartmentsPageComponent,
          ),
        data: { breadcrumb: 'Departments' },
      },
      {
        path: 'organization/offices',
        loadComponent: () =>
          import('./features/organization/offices/offices-page').then((m) => m.OfficesPageComponent),
        data: { breadcrumb: 'Offices' },
      },
      {
        path: 'organization/facilities',
        loadComponent: () =>
          import('./features/organization/facilities/facilities-page').then(
            (m) => m.FacilitiesPageComponent,
          ),
        data: { breadcrumb: 'Facilities' },
      },
      {
        path: 'organization/geography',
        loadComponent: () =>
          import('./features/organization/geography/geography-page').then(
            (m) => m.GeographyPageComponent,
          ),
        data: { breadcrumb: 'Geographic Structure' },
      },
      {
        path: 'organization/sectors',
        loadComponent: () =>
          import('./features/organization/sectors/sectors-page').then((m) => m.SectorsPageComponent),
        data: { breadcrumb: 'Sectors' },
      },
      {
        path: 'organization/users-teams',
        loadComponent: () =>
          import('./features/organization/users-teams/users-teams-page').then(
            (m) => m.UsersTeamsPageComponent,
          ),
        data: { breadcrumb: 'Users & Teams' },
      },
      {
        path: 'organization/reporting-calendars',
        loadComponent: () =>
          import('./features/organization/reporting-calendars/reporting-calendars-page').then(
            (m) => m.ReportingCalendarsPageComponent,
          ),
        data: { breadcrumb: 'Reporting Calendars' },
      },
      {
        path: 'organization/settings',
        loadComponent: () =>
          import('./features/organization/organization-settings/organization-settings-page').then(
            (m) => m.OrganizationSettingsPageComponent,
          ),
        data: { breadcrumb: 'Organization Settings' },
      },
      {
        path: 'strategy/strategic-plans',
        loadComponent: () =>
          import('./features/strategy/strategic-plans/strategic-plans-page').then(
            (m) => m.StrategicPlansPageComponent,
          ),
        data: { breadcrumb: 'Strategic Plans' },
      },
      {
        path: 'strategy/strategic-plans/:id',
        loadComponent: () =>
          import('./features/strategy/strategic-plans/strategic-plan-detail-page').then(
            (m) => m.StrategicPlanDetailPageComponent,
          ),
        data: { breadcrumb: 'Strategic Plan' },
      },
      {
        path: 'strategy/goals',
        loadComponent: () =>
          import('./features/strategy/goals-objectives/goals-objectives-page').then(
            (m) => m.GoalsObjectivesPageComponent,
          ),
        data: { breadcrumb: 'Goals & Objectives' },
      },
      {
        path: 'strategy/annual-work-plans',
        loadComponent: () =>
          import('./features/strategy/annual-work-plans/annual-work-plans-page').then(
            (m) => m.AnnualWorkPlansPageComponent,
          ),
        data: { breadcrumb: 'Annual Work Plans' },
      },
      {
        path: 'strategy/annual-work-plans/:id',
        loadComponent: () =>
          import('./features/strategy/annual-work-plans/annual-work-plan-detail-page').then(
            (m) => m.AnnualWorkPlanDetailPageComponent,
          ),
        data: { breadcrumb: 'Annual Work Plan' },
      },
      {
        path: 'programme/programmes',
        loadComponent: () =>
          import('./features/programme/programmes/programmes-page').then(
            (m) => m.ProgrammesPageComponent,
          ),
        data: { breadcrumb: 'Programmes' },
      },
      {
        path: 'programme/programmes/:id',
        loadComponent: () =>
          import('./features/programme/programmes/programme-detail-page').then(
            (m) => m.ProgrammeDetailPageComponent,
          ),
        data: { breadcrumb: 'Programme' },
      },
      {
        path: 'programme/projects',
        loadComponent: () =>
          import('./features/programme/projects/projects-page').then((m) => m.ProjectsPageComponent),
        data: { breadcrumb: 'Projects' },
      },
      {
        path: 'programme/projects/:id',
        loadComponent: () =>
          import('./features/programme/projects/project-detail-page').then(
            (m) => m.ProjectDetailPageComponent,
          ),
        data: { breadcrumb: 'Project' },
      },
      {
        path: 'programme/results-frameworks',
        loadComponent: () =>
          import('./features/programme/results-framework/results-frameworks-page').then(
            (m) => m.ResultsFrameworksPageComponent,
          ),
        data: { breadcrumb: 'Results Frameworks' },
      },
      {
        path: 'programme/results-frameworks/:id',
        loadComponent: () =>
          import('./features/programme/results-framework/results-framework-detail-page').then(
            (m) => m.ResultsFrameworkDetailPageComponent,
          ),
        data: { breadcrumb: 'Results Framework' },
      },
      {
        path: 'meal/indicators',
        loadComponent: () =>
          import('./features/meal/indicators/indicators-page').then((m) => m.IndicatorsPageComponent),
        data: { breadcrumb: 'Indicator Library' },
      },
      {
        path: 'meal/indicators/:id',
        loadComponent: () =>
          import('./features/meal/indicators/indicator-detail-page').then(
            (m) => m.IndicatorDetailPageComponent,
          ),
        data: { breadcrumb: 'Indicator' },
      },
      {
        path: 'meal/monitoring-plans',
        loadComponent: () =>
          import('./features/meal/monitoring-plans/monitoring-plans-page').then(
            (m) => m.MonitoringPlansPageComponent,
          ),
        data: { breadcrumb: 'Monitoring Plans' },
      },
      {
        path: 'meal/monitoring-plans/:id',
        loadComponent: () =>
          import('./features/meal/monitoring-plans/monitoring-plan-detail-page').then(
            (m) => m.MonitoringPlanDetailPageComponent,
          ),
        data: { breadcrumb: 'Monitoring Plan' },
      },
      {
        path: 'meal/data-collection',
        loadComponent: () =>
          import('./features/meal/data-collection/assignments-page').then(
            (m) => m.AssignmentsPageComponent,
          ),
        data: { breadcrumb: 'Data Collection' },
      },
      {
        path: 'meal/data-collection/submissions/:id',
        loadComponent: () =>
          import('./features/meal/data-collection/submission-detail-page').then(
            (m) => m.SubmissionDetailPageComponent,
          ),
        data: { breadcrumb: 'Submission' },
      },
      {
        path: 'meal/dqa',
        loadComponent: () => import('./features/meal/dqa/dqa-page').then((m) => m.DqaPageComponent),
        data: { breadcrumb: 'Data Quality Assessment' },
      },
      {
        path: 'meal/dqa/:id',
        loadComponent: () =>
          import('./features/meal/dqa/assessment-detail-page').then(
            (m) => m.AssessmentDetailPageComponent,
          ),
        data: { breadcrumb: 'DQA Assessment' },
      },
      {
        path: 'performance/dashboard',
        loadComponent: () =>
          import('./features/performance/dashboard/performance-dashboard-page').then(
            (m) => m.PerformanceDashboardPageComponent,
          ),
        data: { breadcrumb: 'Performance Dashboard' },
      },
      {
        path: 'performance/reports',
        loadComponent: () =>
          import('./features/performance/reports/reports-page').then((m) => m.ReportsPageComponent),
        data: { breadcrumb: 'Reports' },
      },
      {
        path: 'performance/reports/:id',
        loadComponent: () =>
          import('./features/performance/reports/report-detail-page').then(
            (m) => m.ReportDetailPageComponent,
          ),
        data: { breadcrumb: 'Report' },
      },
      {
        path: 'performance/reporting-calendar',
        loadComponent: () =>
          import(
            './features/performance/reporting-calendar/reporting-calendar-hub-page'
          ).then((m) => m.ReportingCalendarHubPageComponent),
        data: { breadcrumb: 'Reporting Calendar' },
      },
      {
        path: 'learning/evaluations',
        loadComponent: () =>
          import('./features/learning/evaluations/evaluations-page').then(
            (m) => m.EvaluationsPageComponent,
          ),
        data: { breadcrumb: 'Evaluations' },
      },
      {
        path: 'learning/evaluations/:id',
        loadComponent: () =>
          import('./features/learning/evaluations/evaluation-detail-page').then(
            (m) => m.EvaluationDetailPageComponent,
          ),
        data: { breadcrumb: 'Evaluation' },
      },
      {
        path: 'learning/feedback',
        loadComponent: () =>
          import('./features/learning/feedback/feedback-page').then((m) => m.FeedbackPageComponent),
        data: { breadcrumb: 'Feedback & Complaints' },
      },
      {
        path: 'learning/feedback/:id',
        loadComponent: () =>
          import('./features/learning/feedback/feedback-detail-page').then(
            (m) => m.FeedbackDetailPageComponent,
          ),
        data: { breadcrumb: 'Feedback Case' },
      },
      {
        path: 'learning/lessons',
        loadComponent: () =>
          import('./features/learning/lessons/lessons-page').then((m) => m.LessonsPageComponent),
        data: { breadcrumb: 'Lessons Learned' },
      },
      {
        path: 'learning/lessons/:id',
        loadComponent: () =>
          import('./features/learning/lessons/lesson-detail-page').then(
            (m) => m.LessonDetailPageComponent,
          ),
        data: { breadcrumb: 'Lesson' },
      },
      {
        path: 'learning/knowledge',
        loadComponent: () =>
          import('./features/learning/knowledge/knowledge-page').then(
            (m) => m.KnowledgePageComponent,
          ),
        data: { breadcrumb: 'Knowledge Repository' },
      },
      {
        path: 'learning/knowledge/:id',
        loadComponent: () =>
          import('./features/learning/knowledge/knowledge-detail-page').then(
            (m) => m.KnowledgeDetailPageComponent,
          ),
        data: { breadcrumb: 'Knowledge Document' },
      },
      {
        path: 'coming-soon/:moduleId',
        loadComponent: () =>
          import('./features/coming-soon/coming-soon-page').then((m) => m.ComingSoonPageComponent),
        data: { breadcrumb: 'Coming Soon' },
      },
      {
        path: '**',
        loadComponent: () =>
          import('./features/coming-soon/coming-soon-page').then((m) => m.ComingSoonPageComponent),
        data: { breadcrumb: 'Coming Soon' },
      },
    ],
  },
  { path: '', pathMatch: 'full', redirectTo: 'app/dashboard' },
  { path: '**', redirectTo: 'app/dashboard' },
];
