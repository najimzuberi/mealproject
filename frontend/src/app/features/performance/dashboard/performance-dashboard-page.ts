import { DecimalPipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatSelectModule } from '@angular/material/select';
import { MatTableModule } from '@angular/material/table';
import { BaseChartDirective } from 'ng2-charts';
import { ChartConfiguration } from 'chart.js';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import {
  AttentionProject,
  emptyPerformanceDashboard,
  IndicatorPerformanceRow,
  PerformanceDashboard,
  PerformanceProjectRow,
  statusLabel,
} from '../../../core/models/performance.model';
import { Programme } from '../../../core/models/programme-project.model';
import { Sector } from '../../../core/models/organization.model';
import { PerformanceService } from '../../../core/services/performance.service';
import { ProgrammeService } from '../../../core/services/programme.service';
import { ProjectService } from '../../../core/services/project.service';
import { SectorService } from '../../../core/services/sector.service';
import { ReportingCalendarService } from '../../../core/services/reporting-calendar.service';
import { ReportingPeriod } from '../../../core/models/organization.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatCardComponent } from '../../../shared/components/stat-card/stat-card';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-performance-dashboard-page',
  standalone: true,
  imports: [
    DecimalPipe,
    ReactiveFormsModule,
    RouterLink,
    MatButtonModule,
    MatFormFieldModule,
    MatIconModule,
    MatSelectModule,
    MatTableModule,
    BaseChartDirective,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatCardComponent,
    StatusChipComponent,
  ],
  templateUrl: './performance-dashboard-page.html',
  styleUrl: './performance-dashboard-page.scss',
})
export class PerformanceDashboardPageComponent {
  private readonly performanceService = inject(PerformanceService);
  private readonly programmeService = inject(ProgrammeService);
  private readonly projectService = inject(ProjectService);
  private readonly sectorService = inject(SectorService);
  private readonly calendarService = inject(ReportingCalendarService);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly dashboard = signal<PerformanceDashboard>(emptyPerformanceDashboard());
  readonly indicatorRows = signal<IndicatorPerformanceRow[]>([]);
  readonly programmes = signal<Programme[]>([]);
  readonly projectOptions = signal<
    Array<{ id: number; name: string; programme_id?: number | null }>
  >([]);
  readonly sectors = signal<Sector[]>([]);
  readonly periods = signal<ReportingPeriod[]>([]);
  readonly statusLabel = statusLabel;

  readonly projectColumns = ['name', 'programme', 'achievement', 'status'];
  readonly indicatorColumns = [
    'indicator',
    'project',
    'target',
    'actual',
    'achievement',
    'status',
  ];
  readonly attentionColumns = ['name', 'achievement', 'reason', 'status'];

  readonly filters = this.fb.nonNullable.group({
    programme_id: ['' as string | number],
    project_id: ['' as string | number],
    reporting_period_id: ['' as string | number],
    sector_id: ['' as string | number],
  });

  readonly chartOptions: ChartConfiguration<'bar'>['options'] = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: { boxWidth: 10, boxHeight: 10, padding: 14, font: { size: 12 } },
      },
    },
    scales: {
      x: { grid: { display: false } },
      y: {
        beginAtZero: true,
        ticks: { precision: 0 },
      },
    },
  };

  readonly chartData = computed(() => this.buildChartData(this.dashboard()));

  constructor() {
    this.loadFilterOptions();
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const raw = this.filters.getRawValue();
    const query = {
      programme_id: raw.programme_id === '' ? undefined : Number(raw.programme_id),
      project_id: raw.project_id === '' ? undefined : Number(raw.project_id),
      reporting_period_id:
        raw.reporting_period_id === '' ? undefined : Number(raw.reporting_period_id),
      sector_id: raw.sector_id === '' ? undefined : Number(raw.sector_id),
      per_page: 25,
    };

    forkJoin({
      dashboard: this.performanceService.dashboard(query),
      indicators: this.performanceService.indicatorResults(query),
    }).subscribe({
      next: ({ dashboard, indicators }) => {
        this.dashboard.set(dashboard);
        const rows =
          indicators.items.length > 0
            ? indicators.items
            : (dashboard.indicators ?? []);
        this.indicatorRows.set(rows);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load performance dashboard.');
        this.loading.set(false);
      },
    });
  }

  summaryValue(key: string): string | number {
    const summary = this.dashboard().summary ?? {};
    const value = summary[key];
    if (value === null || value === undefined || value === '') {
      return '—';
    }
    return value;
  }

  projectRows(): PerformanceProjectRow[] {
    return this.dashboard().projects ?? [];
  }

  attentionProjects(): AttentionProject[] {
    return this.dashboard().attention_projects ?? [];
  }

  attentionCount(): number | string {
    const summary = this.dashboard().summary ?? {};
    const atRisk = Number(summary['at_risk_count'] ?? 0);
    const offTrack = Number(summary['off_track_count'] ?? 0);
    const total = atRisk + offTrack;
    return Number.isFinite(total) ? total : '—';
  }

  hasChartData(): boolean {
    const chart = this.dashboard().target_vs_achievement;
    if (chart?.labels?.length) {
      return true;
    }
    const trends = this.dashboard().trends ?? [];
    if (trends.some((t) => t.actual != null || t.achievement != null || t.target != null)) {
      return true;
    }
    return this.indicatorRows().some((row) => row.target != null || row.actual != null);
  }

  private loadFilterOptions(): void {
    forkJoin({
      programmes: this.programmeService.list({ per_page: 100 }).pipe(
        catchError(() => of({ items: [] as Programme[] })),
      ),
      projects: this.projectService.list({ per_page: 100 }).pipe(
        catchError(() => of({ items: [] as Array<{ id: number; name: string }> })),
      ),
      sectors: this.sectorService.list({ per_page: 100 }).pipe(
        catchError(() => of({ items: [] as Sector[] })),
      ),
      calendars: this.calendarService.list({ per_page: 20 }).pipe(
        catchError(() => of({ items: [] })),
      ),
    }).subscribe({
      next: ({ programmes, projects, sectors, calendars }) => {
        this.programmes.set(programmes.items);
        this.projectOptions.set(projects.items);
        this.sectors.set(sectors.items);
        const periods: ReportingPeriod[] = [];
        for (const calendar of calendars.items) {
          if (calendar.periods?.length) {
            periods.push(...calendar.periods);
          }
        }
        if (periods.length === 0 && calendars.items[0]) {
          this.calendarService.listPeriods(calendars.items[0].id).subscribe({
            next: (rows) => this.periods.set(rows),
            error: () => this.periods.set([]),
          });
        } else {
          this.periods.set(periods);
        }
      },
    });
  }

  private buildChartData(
    dashboard: PerformanceDashboard,
  ): ChartConfiguration<'bar'>['data'] {
    const chart = dashboard.target_vs_achievement;
    if (chart?.labels?.length) {
      return {
        labels: chart.labels,
        datasets: [
          {
            label: 'Target',
            data: chart.targets.map((v) => Number(v ?? 0)),
            backgroundColor: '#142B45',
            borderRadius: 4,
          },
          {
            label: 'Actual',
            data: chart.actuals.map((v) => Number(v ?? 0)),
            backgroundColor: '#117C78',
            borderRadius: 4,
          },
        ],
      };
    }

    const trends = dashboard.trends ?? [];
    if (trends.some((t) => t.actual != null || t.achievement != null || t.target != null)) {
      return {
        labels: trends.map((t) => t.label || t.period || 'Period'),
        datasets: [
          {
            label: 'Target',
            data: trends.map((t) => Number(t.target ?? 0)),
            backgroundColor: '#142B45',
            borderRadius: 4,
          },
          {
            label: 'Achievement',
            data: trends.map((t) => Number(t.achievement ?? t.actual ?? 0)),
            backgroundColor: '#117C78',
            borderRadius: 4,
          },
        ],
      };
    }

    const rows = this.indicatorRows().slice(0, 8);
    return {
      labels: rows.map(
        (row) => row.indicator_code || row.indicator_name || `Indicator ${row.indicator_id ?? ''}`,
      ),
      datasets: [
        {
          label: 'Target',
          data: rows.map((row) => Number(row.target ?? 0)),
          backgroundColor: '#142B45',
          borderRadius: 4,
        },
        {
          label: 'Achievement',
          data: rows.map((row) => Number(row.actual ?? 0)),
          backgroundColor: '#117C78',
          borderRadius: 4,
        },
      ],
    };
  }
}
