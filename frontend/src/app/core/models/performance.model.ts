/** Performance dashboard, indicator results, reports, and calendar compliance. */

export type ReportStatus =
  | 'draft'
  | 'under_review'
  | 'returned'
  | 'approved'
  | 'published'
  | string;

export type ReportType =
  | 'monthly'
  | 'quarterly'
  | 'annual'
  | 'project'
  | 'programme'
  | 'indicator'
  | 'monitoring'
  | 'compliance'
  | string;

export type PerformanceStatus =
  | 'on_track'
  | 'at_risk'
  | 'off_track'
  | 'exceeded'
  | 'not_started'
  | string;

export const REPORT_STATUSES = [
  'draft',
  'under_review',
  'returned',
  'approved',
  'published',
] as const;

export const REPORT_TYPES = [
  'monthly',
  'quarterly',
  'annual',
  'project',
  'programme',
  'indicator',
  'monitoring',
  'compliance',
] as const;

export interface PerformanceListQuery {
  q?: string;
  search?: string;
  status?: string;
  report_type?: string;
  programme_id?: number;
  project_id?: number;
  reporting_period_id?: number;
  sector_id?: number;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: string;
  [key: string]: string | number | boolean | null | undefined;
}

export interface PerformanceSummaryCards {
  projects_count?: number;
  indicators_count?: number;
  avg_achievement_percent?: number | null;
  reports_pending?: number;
  on_track_count?: number;
  at_risk_count?: number;
  off_track_count?: number;
  completion_percent?: number | null;
  [key: string]: number | string | null | undefined;
}

export interface PerformanceProjectRow {
  id: number;
  name: string;
  code?: string | null;
  programme_id?: number | null;
  programme_name?: string | null;
  status?: string | null;
  achievement_percent?: number | null;
  indicators_count?: number | null;
  performance_status?: PerformanceStatus | null;
}

export interface PerformanceTrendPoint {
  label: string;
  target?: number | null;
  achievement?: number | null;
  actual?: number | null;
  period?: string | null;
}

export interface PerformanceReportingStatusItem {
  status: string;
  count: number;
  label?: string | null;
}

export interface AttentionProject {
  id: number;
  name: string;
  code?: string | null;
  reason?: string | null;
  achievement_percent?: number | null;
  performance_status?: PerformanceStatus | null;
}

export interface TargetVsAchievementChart {
  labels: string[];
  targets: Array<number | null>;
  actuals: Array<number | null>;
  granularity?: string | null;
}

export interface PerformanceDashboard {
  summary?: PerformanceSummaryCards | null;
  projects?: PerformanceProjectRow[];
  indicators?: IndicatorPerformanceRow[];
  trends?: PerformanceTrendPoint[];
  reporting_status?: PerformanceReportingStatusItem[] | Record<string, number | string> | null;
  attention_projects?: AttentionProject[];
  target_vs_achievement?: TargetVsAchievementChart | null;
  generated_at?: string | null;
}

export interface IndicatorPerformanceRow {
  id?: number;
  indicator_id?: number | null;
  indicator_code?: string | null;
  indicator_name?: string | null;
  project_id?: number | null;
  project_name?: string | null;
  programme_id?: number | null;
  programme_name?: string | null;
  reporting_period_id?: number | null;
  reporting_period_name?: string | null;
  baseline?: number | null;
  target?: number | null;
  actual?: number | null;
  achievement_percent?: number | null;
  variance?: number | null;
  performance_status?: PerformanceStatus | null;
}

export interface ReportIndicatorResult {
  id: number;
  report_id: number;
  indicator_id?: number | null;
  project_id?: number | null;
  reporting_period_id?: number | null;
  indicator_code?: string | null;
  indicator_name?: string | null;
  baseline?: number | null;
  target?: number | null;
  actual?: number | null;
  achievement_percent?: number | null;
  variance?: number | null;
  performance_status?: PerformanceStatus | null;
}

export interface Report {
  id: number;
  ulid?: string;
  organization_id?: number;
  reference_number: string;
  title: string;
  report_type: ReportType;
  status: ReportStatus;
  programme_id?: number | null;
  project_id?: number | null;
  reporting_period_id?: number | null;
  starts_on?: string | null;
  ends_on?: string | null;
  prepared_by?: number | null;
  reviewed_by?: number | null;
  approved_by?: number | null;
  executive_summary?: string | null;
  performance_summary?: string | null;
  challenges?: string | null;
  risks?: string | null;
  recommendations?: string | null;
  ai_assisted?: boolean;
  snapshot?: Record<string, unknown> | null;
  submitted_at?: string | null;
  approved_at?: string | null;
  published_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  programme?: { id: number; name: string; code?: string | null } | null;
  project?: { id: number; name: string; code?: string | null } | null;
  reporting_period?: { id: number; name: string } | null;
  indicator_results?: ReportIndicatorResult[];
}

export interface CompliancePeriodRow {
  id: number;
  name: string;
  status: string;
  reporting_calendar_id?: number | null;
  calendar_name?: string | null;
  starts_on?: string | null;
  ends_on?: string | null;
  submission_deadline?: string | null;
  days_overdue?: number | null;
}

export interface CalendarCompliance {
  completion_percent?: number | null;
  overdue_count?: number | null;
  open_count?: number | null;
  closed_count?: number | null;
  calendars_count?: number | null;
  overdue?: CompliancePeriodRow[];
  open_periods?: CompliancePeriodRow[];
  generated_at?: string | null;
}

export function statusLabel(status: string | null | undefined): string {
  if (!status) {
    return '—';
  }
  return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function emptyPerformanceDashboard(): PerformanceDashboard {
  return {
    summary: {
      projects_count: 0,
      indicators_count: 0,
      avg_achievement_percent: null,
      reports_pending: 0,
      on_track_count: 0,
      at_risk_count: 0,
      off_track_count: 0,
    },
    projects: [],
    indicators: [],
    trends: [],
    reporting_status: [],
    attention_projects: [],
  };
}

export function emptyCalendarCompliance(): CalendarCompliance {
  return {
    completion_percent: null,
    overdue_count: 0,
    open_count: 0,
    closed_count: 0,
    calendars_count: 0,
    overdue: [],
    open_periods: [],
  };
}
