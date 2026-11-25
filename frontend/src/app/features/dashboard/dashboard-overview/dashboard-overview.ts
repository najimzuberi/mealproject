import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { AuthService } from '../../../core/services/auth.service';
import { DashboardService } from '../../../core/services/dashboard.service';
import { DashboardOverview } from '../../../core/models/api.model';
import { StatCardComponent } from '../../../shared/components/stat-card/stat-card';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { ProjectStatusWidgetComponent } from '../project-status-widget/project-status-widget';
import { ObjectivesProgressWidgetComponent } from '../objectives-progress-widget/objectives-progress-widget';
import { IndicatorPerformanceWidgetComponent } from '../indicator-performance-widget/indicator-performance-widget';
import { ReportingCalendarWidgetComponent } from '../reporting-calendar-widget/reporting-calendar-widget';
import { PendingTasksWidgetComponent } from '../pending-tasks-widget/pending-tasks-widget';
import { RecentActivitiesWidgetComponent } from '../recent-activities-widget/recent-activities-widget';

@Component({
  selector: 'app-dashboard-overview',
  standalone: true,
  imports: [
    DatePipe,
    MatFormFieldModule,
    MatSelectModule,
    StatCardComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    ProjectStatusWidgetComponent,
    ObjectivesProgressWidgetComponent,
    IndicatorPerformanceWidgetComponent,
    ReportingCalendarWidgetComponent,
    PendingTasksWidgetComponent,
    RecentActivitiesWidgetComponent,
  ],
  templateUrl: './dashboard-overview.html',
  styleUrl: './dashboard-overview.scss',
})
export class DashboardOverviewComponent {
  private readonly dashboard = inject(DashboardService);
  private readonly router = inject(Router);
  readonly auth = inject(AuthService);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly data = signal<DashboardOverview | null>(null);
  readonly today = new Date();
  period = 'current';

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.dashboard.overview().subscribe({
      next: (overview) => {
        this.data.set(overview);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Dashboard data could not be loaded.');
        this.loading.set(false);
      },
    });
  }

  go(path: string): void {
    void this.router.navigateByUrl(path);
  }
}
