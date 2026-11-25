import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MonitoringPlanService } from '../../../core/services/monitoring-plan.service';
import { IndicatorService } from '../../../core/services/indicator.service';
import { ProjectService } from '../../../core/services/project.service';
import {
  Indicator,
  MonitoringPlan,
  approvalEntries,
  statusLabel,
} from '../../../core/models/meal.model';
import { Project } from '../../../core/models/programme-project.model';
import { MonitoringPlanFormDialogComponent } from './monitoring-plans-page';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';
import {
  ConfirmDialogComponent,
  ConfirmDialogData,
} from '../../../shared/components/confirm-dialog/confirm-dialog';

@Component({
  selector: 'app-monitoring-plan-detail-page',
  standalone: true,
  imports: [
    RouterLink,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatTableModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './monitoring-plan-detail-page.html',
  styleUrl: './monitoring-plan-detail-page.scss',
})
export class MonitoringPlanDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly planService = inject(MonitoringPlanService);
  private readonly projectService = inject(ProjectService);
  private readonly indicatorService = inject(IndicatorService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly plan = signal<MonitoringPlan | null>(null);
  readonly versions = signal<MonitoringPlan[]>([]);
  readonly projects = signal<Project[]>([]);
  readonly indicators = signal<Indicator[]>([]);
  readonly statusLabel = statusLabel;
  readonly approvalEntries = approvalEntries;
  readonly itemColumns = [
    'indicator',
    'baseline_value',
    'target_value',
    'collection_frequency',
    'deadline',
  ];

  private planId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid monitoring plan.');
      this.loading.set(false);
      return;
    }
    this.planId = id;
    this.loadLookups();
    this.reload();
  }

  private loadLookups(): void {
    this.projectService.list({ per_page: 100 }).subscribe({
      next: (page) => this.projects.set(page.items),
    });
    this.indicatorService.list({ per_page: 100 }).subscribe({
      next: (page) => this.indicators.set(page.items),
    });
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.planService.get(this.planId).subscribe({
      next: (plan) => {
        this.plan.set(plan);
        this.loading.set(false);
        this.loadVersions();
      },
      error: () => {
        this.error.set('Unable to load monitoring plan.');
        this.loading.set(false);
      },
    });
  }

  private loadVersions(): void {
    this.planService.versions(this.planId).subscribe({
      next: (rows) => this.versions.set(rows),
      error: () => this.versions.set([]),
    });
  }

  openEdit(): void {
    const current = this.plan();
    if (!current) {
      return;
    }
    this.dialog
      .open(MonitoringPlanFormDialogComponent, {
        width: '800px',
        data: {
          plan: current,
          projects: this.projects(),
          indicators: this.indicators(),
        },
      })
      .afterClosed()
      .subscribe((payload) => {
        if (!payload) {
          return;
        }
        this.planService.update(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Monitoring plan updated.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  runWorkflow(
    action: 'submit' | 'return' | 'approve' | 'activate' | 'archive',
  ): void {
    const current = this.plan();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit for review',
        message: `Submit “${current.title}” for review?`,
        confirmLabel: 'Submit',
      },
      return: {
        title: 'Return plan',
        message: `Return “${current.title}” for correction?`,
        confirmLabel: 'Return',
      },
      approve: {
        title: 'Approve plan',
        message: `Approve “${current.title}”?`,
        confirmLabel: 'Approve',
      },
      activate: {
        title: 'Activate plan',
        message: `Activate “${current.title}”? Assignments may be generated.`,
        confirmLabel: 'Activate',
      },
      archive: {
        title: 'Archive plan',
        message: `Archive “${current.title}”?`,
        confirmLabel: 'Archive',
        danger: true,
      },
    };
    this.dialog
      .open(ConfirmDialogComponent, { data: labels[action] })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        const request$ =
          action === 'submit'
            ? this.planService.submit(current.id)
            : action === 'return'
              ? this.planService.returnPlan(current.id)
              : action === 'approve'
                ? this.planService.approve(current.id)
                : action === 'activate'
                  ? this.planService.activate(current.id)
                  : this.planService.archive(current.id);
        request$.subscribe({
          next: () => {
            this.snackBar.open('Workflow action completed.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Action failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  goBack(): void {
    void this.router.navigate(['/app/meal/monitoring-plans']);
  }

  canEdit(plan: MonitoringPlan): boolean {
    return plan.status === 'draft' || plan.status === 'returned';
  }

  canSubmit(plan: MonitoringPlan): boolean {
    return plan.status === 'draft' || plan.status === 'returned';
  }

  canReturn(plan: MonitoringPlan): boolean {
    return plan.status === 'under_review';
  }

  canApprove(plan: MonitoringPlan): boolean {
    return plan.status === 'under_review';
  }

  canActivate(plan: MonitoringPlan): boolean {
    return plan.status === 'approved';
  }

  canArchive(plan: MonitoringPlan): boolean {
    return plan.status === 'active' || plan.status === 'approved';
  }

  indicatorLabel(item: { indicator_id: number; indicator?: { code?: string; name?: string } | null }): string {
    if (item.indicator?.name) {
      return item.indicator.code
        ? `${item.indicator.code} — ${item.indicator.name}`
        : item.indicator.name;
    }
    const match = this.indicators().find((i) => i.id === item.indicator_id);
    return match ? `${match.code} — ${match.name}` : `#${item.indicator_id}`;
  }
}
