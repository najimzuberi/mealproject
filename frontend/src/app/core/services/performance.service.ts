import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  AttentionProject,
  CalendarCompliance,
  CompliancePeriodRow,
  emptyCalendarCompliance,
  emptyPerformanceDashboard,
  IndicatorPerformanceRow,
  PerformanceDashboard,
  PerformanceListQuery,
  PerformanceProjectRow,
  PerformanceTrendPoint,
} from '../models/performance.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

function emptyPage<T>(): Paginated<T> {
  return {
    items: [],
    pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1 },
  };
}

function tolerateNotFoundList<T>(): (err: unknown) => Observable<Paginated<T>> {
  return (err: unknown) => {
    if (err instanceof HttpErrorResponse && err.status === 404) {
      return of(emptyPage<T>());
    }
    return throwError(() => err);
  };
}

function tolerateNotFoundDashboard(): (err: unknown) => Observable<PerformanceDashboard> {
  return (err: unknown) => {
    if (err instanceof HttpErrorResponse && err.status === 404) {
      return of(emptyPerformanceDashboard());
    }
    return throwError(() => err);
  };
}

function tolerateNotFoundCompliance(): (err: unknown) => Observable<CalendarCompliance> {
  return (err: unknown) => {
    if (err instanceof HttpErrorResponse && err.status === 404) {
      return of(emptyCalendarCompliance());
    }
    return throwError(() => err);
  };
}

function daysPastDeadline(deadline?: string | null): number | null {
  if (!deadline) {
    return null;
  }
  const end = new Date(`${deadline}T23:59:59`);
  if (Number.isNaN(end.getTime())) {
    return null;
  }
  const diff = Date.now() - end.getTime();
  if (diff <= 0) {
    return 0;
  }
  return Math.floor(diff / (1000 * 60 * 60 * 24));
}

function mapPeriodRow(row: Record<string, unknown>, fallbackStatus = 'open'): CompliancePeriodRow {
  return {
    id: Number(row['id']),
    name: String(row['name'] ?? ''),
    status: String(row['status'] ?? fallbackStatus),
    reporting_calendar_id: (row['reporting_calendar_id'] as number | null | undefined) ?? null,
    calendar_name: (row['calendar_name'] as string | null | undefined)
      ?? (row['calendar'] as string | null | undefined)
      ?? null,
    starts_on: (row['starts_on'] as string | null | undefined) ?? null,
    ends_on: (row['ends_on'] as string | null | undefined) ?? null,
    submission_deadline: (row['submission_deadline'] as string | null | undefined) ?? null,
    days_overdue: (row['days_overdue'] as number | null | undefined)
      ?? daysPastDeadline((row['submission_deadline'] as string | null | undefined) ?? null),
  };
}

function mapIndicatorRow(row: Record<string, unknown>): IndicatorPerformanceRow {
  return {
    id: (row['indicator_id'] as number | undefined) ?? undefined,
    indicator_id: (row['indicator_id'] as number | null | undefined) ?? null,
    indicator_code: (row['indicator_code'] as string | null | undefined) ?? null,
    indicator_name: (row['indicator_name'] as string | null | undefined) ?? null,
    project_id: (row['project_id'] as number | null | undefined) ?? null,
    project_name: (row['project_name'] as string | null | undefined) ?? null,
    reporting_period_id: (row['reporting_period_id'] as number | null | undefined) ?? null,
    reporting_period_name:
      (row['reporting_period_name'] as string | null | undefined)
      ?? (row['reporting_period'] as string | null | undefined)
      ?? null,
    baseline: (row['baseline'] as number | null | undefined) ?? null,
    target: (row['target'] as number | null | undefined) ?? null,
    actual: (row['actual'] as number | null | undefined) ?? null,
    achievement_percent: (row['achievement_percent'] as number | null | undefined) ?? null,
    variance: (row['variance'] as number | null | undefined) ?? null,
    performance_status: (row['performance_status'] as string | null | undefined) ?? null,
  };
}

function mapProjectRow(row: Record<string, unknown>): PerformanceProjectRow {
  return {
    id: Number(row['project_id'] ?? row['id'] ?? 0),
    name: String(row['project_name'] ?? row['name'] ?? ''),
    code: (row['project_code'] as string | null | undefined) ?? (row['code'] as string | null | undefined) ?? null,
    programme_id: (row['programme_id'] as number | null | undefined) ?? null,
    programme_name: (row['programme_name'] as string | null | undefined) ?? null,
    status: (row['status'] as string | null | undefined) ?? null,
    achievement_percent:
      (row['achievement_percent'] as number | null | undefined)
      ?? (row['completion_percent'] as number | null | undefined)
      ?? (row['actual_progress'] as number | null | undefined)
      ?? null,
    indicators_count: (row['indicators_count'] as number | null | undefined) ?? null,
    performance_status: (row['performance_status'] as string | null | undefined) ?? null,
  };
}

function mapAttention(row: Record<string, unknown>): AttentionProject {
  const reasons = row['reasons'];
  const reason = Array.isArray(reasons)
    ? reasons.map((r) => String(r).replace(/_/g, ' ')).join(', ')
    : ((row['reason'] as string | null | undefined) ?? null);

  return {
    id: Number(row['project_id'] ?? row['id'] ?? 0),
    name: String(row['project_name'] ?? row['name'] ?? ''),
    code: (row['project_code'] as string | null | undefined) ?? null,
    reason,
    achievement_percent: (row['achievement_percent'] as number | null | undefined) ?? null,
    performance_status: (row['performance_status'] as string | null | undefined) ?? null,
  };
}

function mapTrend(row: Record<string, unknown>): PerformanceTrendPoint {
  return {
    label: String(row['label'] ?? row['period'] ?? ''),
    target: (row['target'] as number | null | undefined) ?? null,
    achievement: (row['achievement'] as number | null | undefined) ?? null,
    actual:
      (row['actual'] as number | null | undefined)
      ?? (row['average_actual'] as number | null | undefined)
      ?? null,
    period: (row['period'] as string | null | undefined) ?? (row['starts_on'] as string | null | undefined) ?? null,
  };
}

/** Normalize Laravel performance dashboard payload to the Angular view model. */
export function normalizePerformanceDashboard(raw: unknown): PerformanceDashboard {
  if (!raw || typeof raw !== 'object') {
    return emptyPerformanceDashboard();
  }

  const data = raw as Record<string, unknown>;
  const summaryRaw = (data['summary'] as Record<string, unknown> | undefined) ?? {};
  const indicatorRows = Array.isArray(data['indicator_performance'])
    ? (data['indicator_performance'] as Record<string, unknown>[]).map(mapIndicatorRow)
    : Array.isArray(data['indicators'])
      ? (data['indicators'] as Record<string, unknown>[]).map(mapIndicatorRow)
      : [];
  const projectRows = Array.isArray(data['project_performance'])
    ? (data['project_performance'] as Record<string, unknown>[]).map(mapProjectRow)
    : Array.isArray(data['projects'])
      ? (data['projects'] as Record<string, unknown>[]).map(mapProjectRow)
      : [];

  const achievements = indicatorRows
    .map((r) => r.achievement_percent)
    .filter((v): v is number => v != null && Number.isFinite(Number(v)))
    .map(Number);
  const avgAchievement =
    achievements.length > 0
      ? Math.round((achievements.reduce((a, b) => a + b, 0) / achievements.length) * 10) / 10
      : null;

  const targetVs = data['target_vs_achievement'] as
    | { labels?: string[]; targets?: Array<number | null>; actuals?: Array<number | null> }
    | undefined;

  return {
    summary: {
      projects_count: Number(summaryRaw['total_projects'] ?? summaryRaw['projects_count'] ?? 0),
      indicators_count: Number(summaryRaw['total_indicators'] ?? summaryRaw['indicators_count'] ?? 0),
      avg_achievement_percent: (summaryRaw['avg_achievement_percent'] as number | null | undefined) ?? avgAchievement,
      reports_pending: Number(summaryRaw['pending_reports'] ?? summaryRaw['reports_pending'] ?? 0),
      on_track_count: Number(summaryRaw['indicators_on_track'] ?? summaryRaw['on_track_count'] ?? 0),
      at_risk_count: Number(summaryRaw['indicators_at_risk'] ?? summaryRaw['at_risk_count'] ?? 0),
      off_track_count: Number(summaryRaw['indicators_off_track'] ?? summaryRaw['off_track_count'] ?? 0),
      completion_percent: (summaryRaw['completion_percent'] as number | null | undefined) ?? null,
      active_projects: Number(summaryRaw['active_projects'] ?? 0),
      completed_projects: Number(summaryRaw['completed_projects'] ?? 0),
    },
    projects: projectRows,
    indicators: indicatorRows,
    trends: Array.isArray(data['trends'])
      ? (data['trends'] as Record<string, unknown>[]).map(mapTrend)
      : [],
    reporting_status: Array.isArray(data['reporting_status'])
      ? (data['reporting_status'] as PerformanceDashboard['reporting_status'])
      : undefined,
    attention_projects: Array.isArray(data['attention_projects'])
      ? (data['attention_projects'] as Record<string, unknown>[]).map(mapAttention)
      : [],
    target_vs_achievement: targetVs
      ? {
          labels: targetVs.labels ?? [],
          targets: targetVs.targets ?? [],
          actuals: targetVs.actuals ?? [],
        }
      : undefined,
    generated_at: (data['generated_at'] as string | null | undefined) ?? null,
  };
}

export function normalizeCalendarCompliance(raw: unknown): CalendarCompliance {
  if (!raw || typeof raw !== 'object') {
    return emptyCalendarCompliance();
  }

  const data = raw as Record<string, unknown>;
  const overdue = Array.isArray(data['overdue_periods'])
    ? (data['overdue_periods'] as Record<string, unknown>[]).map((row) => mapPeriodRow(row, 'overdue'))
    : Array.isArray(data['overdue'])
      ? (data['overdue'] as Record<string, unknown>[]).map((row) => mapPeriodRow(row, 'overdue'))
      : [];
  const openPeriods = Array.isArray(data['open_periods'])
    ? (data['open_periods'] as Record<string, unknown>[]).map((row) => mapPeriodRow(row, 'open'))
    : [];
  const assignments = (data['assignments'] as Record<string, unknown> | undefined) ?? {};

  return {
    completion_percent:
      (data['completion_percent'] as number | null | undefined)
      ?? (assignments['completion_percent'] as number | null | undefined)
      ?? null,
    overdue_count: (data['overdue_count'] as number | null | undefined) ?? overdue.length,
    open_count: (data['open_count'] as number | null | undefined) ?? openPeriods.length,
    closed_count: (data['closed_count'] as number | null | undefined) ?? 0,
    calendars_count: (data['calendars_count'] as number | null | undefined) ?? null,
    overdue,
    open_periods: openPeriods,
    generated_at: (data['generated_at'] as string | null | undefined) ?? null,
  };
}

@Injectable({ providedIn: 'root' })
export class PerformanceService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/performance`;
  private readonly calendarsBase = `${environment.apiBaseUrl}/reporting-calendars`;

  dashboard(query?: PerformanceListQuery): Observable<PerformanceDashboard> {
    return this.http
      .get<ApiResponse<unknown>>(`${this.base}/dashboard`, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => normalizePerformanceDashboard(res.data)),
        catchError(tolerateNotFoundDashboard()),
      );
  }

  indicatorResults(query?: PerformanceListQuery): Observable<Paginated<IndicatorPerformanceRow>> {
    return this.http
      .get<ApiResponse<{ items?: IndicatorPerformanceRow[] } | IndicatorPerformanceRow[] | Paginated<IndicatorPerformanceRow>>>(
        `${this.base}/indicator-results`,
        { params: toHttpParams(query) },
      )
      .pipe(
        map((res) => {
          const data = res.data;
          if (data && typeof data === 'object' && !Array.isArray(data) && 'items' in data) {
            const items = ((data as { items?: Record<string, unknown>[] }).items ?? []).map(mapIndicatorRow);
            return {
              items,
              pagination: {
                current_page: 1,
                per_page: items.length || 15,
                total: items.length,
                last_page: 1,
              },
            };
          }
          const page = unwrapPaginated(data as IndicatorPerformanceRow[] | Paginated<IndicatorPerformanceRow>);
          return {
            ...page,
            items: page.items.map((row) => mapIndicatorRow(row as unknown as Record<string, unknown>)),
          };
        }),
        catchError(tolerateNotFoundList<IndicatorPerformanceRow>()),
      );
  }

  compliance(): Observable<CalendarCompliance> {
    return this.http
      .get<ApiResponse<unknown>>(`${this.calendarsBase}/compliance`)
      .pipe(
        map((res) => normalizeCalendarCompliance(res.data)),
        catchError(tolerateNotFoundCompliance()),
      );
  }
}
