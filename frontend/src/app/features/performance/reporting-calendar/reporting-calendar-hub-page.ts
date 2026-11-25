import { DatePipe, DecimalPipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import { forkJoin } from 'rxjs';
import {
  CalendarCompliance,
  CompliancePeriodRow,
  emptyCalendarCompliance,
  statusLabel,
} from '../../../core/models/performance.model';
import { ReportingCalendar } from '../../../core/models/organization.model';
import { PerformanceService } from '../../../core/services/performance.service';
import { ReportingCalendarService } from '../../../core/services/reporting-calendar.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatCardComponent } from '../../../shared/components/stat-card/stat-card';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-reporting-calendar-hub-page',
  standalone: true,
  imports: [
    DatePipe,
    DecimalPipe,
    RouterLink,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatTooltipModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatCardComponent,
    StatusChipComponent,
  ],
  templateUrl: './reporting-calendar-hub-page.html',
  styleUrl: './reporting-calendar-hub-page.scss',
})
export class ReportingCalendarHubPageComponent {
  private readonly performanceService = inject(PerformanceService);
  private readonly calendarService = inject(ReportingCalendarService);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly compliance = signal<CalendarCompliance>(emptyCalendarCompliance());
  readonly calendars = signal<ReportingCalendar[]>([]);
  readonly statusLabel = statusLabel;

  readonly calendarColumns = ['name', 'frequency', 'status', 'periods'];
  readonly periodColumns = ['name', 'calendar', 'deadline', 'status', 'days'];

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    forkJoin({
      compliance: this.performanceService.compliance(),
      calendars: this.calendarService.list({ per_page: 50 }),
    }).subscribe({
      next: ({ compliance, calendars }) => {
        this.compliance.set({
          ...compliance,
          calendars_count: compliance.calendars_count ?? calendars.items.length,
        });
        this.calendars.set(calendars.items);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load reporting calendar hub.');
        this.loading.set(false);
      },
    });
  }

  overdueRows(): CompliancePeriodRow[] {
    return this.compliance().overdue ?? [];
  }

  openPeriodRows(): CompliancePeriodRow[] {
    return this.compliance().open_periods ?? [];
  }

  completionLabel(): string | number {
    const value = this.compliance().completion_percent;
    if (value === null || value === undefined) {
      return '—';
    }
    return `${value}%`;
  }
}
