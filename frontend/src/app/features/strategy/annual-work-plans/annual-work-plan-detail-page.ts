import { DecimalPipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { AnnualWorkPlanService } from '../../../core/services/annual-work-plan.service';
import { StrategicPlanService } from '../../../core/services/strategic-plan.service';
import {
  AnnualWorkPlan,
  StrategicPlan,
  flattenActivities,
  statusLabel,
  sumBudgetEstimate,
} from '../../../core/models/strategy.model';
import { AwpFormDialogComponent } from './annual-work-plans-page';
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
  selector: 'app-annual-work-plan-detail-page',
  standalone: true,
  imports: [
    DecimalPipe,
    RouterLink,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatExpansionModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './annual-work-plan-detail-page.html',
  styleUrl: './annual-work-plan-detail-page.scss',
})
export class AnnualWorkPlanDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly awpService = inject(AnnualWorkPlanService);
  private readonly planService = inject(StrategicPlanService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly awp = signal<AnnualWorkPlan | null>(null);
  readonly plans = signal<StrategicPlan[]>([]);
  readonly statusLabel = statusLabel;
  readonly sumBudgetEstimate = sumBudgetEstimate;
  readonly flattenActivities = flattenActivities;

  private awpId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid annual work plan.');
      this.loading.set(false);
      return;
    }
    this.awpId = id;
    this.planService.list({ per_page: 100 }).subscribe({
      next: (page) => this.plans.set(page.items),
    });
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.awpService.get(this.awpId).subscribe({
      next: (awp) => {
        this.awp.set(awp);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load annual work plan.');
        this.loading.set(false);
      },
    });
  }

  planLabel(awp: AnnualWorkPlan): string {
    if (awp.strategic_plan) {
      return `${awp.strategic_plan.code} — ${awp.strategic_plan.title}`;
    }
    const linked = this.plans().find((p) => p.id === awp.strategic_plan_id);
    return linked ? `${linked.code} — ${linked.title}` : String(awp.strategic_plan_id);
  }

  openEdit(): void {
    const current = this.awp();
    if (!current) {
      return;
    }
    const eligiblePlans = this.plans().filter(
      (p) => p.status === 'approved' || p.status === 'active' || p.id === current.strategic_plan_id,
    );
    this.dialog
      .open(AwpFormDialogComponent, {
        width: '720px',
        data: { awp: current, plans: eligiblePlans.length ? eligiblePlans : this.plans() },
      })
      .afterClosed()
      .subscribe((payload) => {
        if (!payload) {
          return;
        }
        this.awpService.update(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('AWP updated.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  runWorkflow(action: 'submit' | 'approve' | 'activate' | 'archive' | 'return'): void {
    const current = this.awp();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit for review',
        message: `Submit “${current.title}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve AWP',
        message: `Approve “${current.title}”?`,
        confirmLabel: 'Approve',
      },
      activate: {
        title: 'Activate AWP',
        message: `Activate “${current.title}”?`,
        confirmLabel: 'Activate',
      },
      archive: {
        title: 'Archive AWP',
        message: `Archive “${current.title}”?`,
        confirmLabel: 'Archive',
      },
      return: {
        title: 'Return AWP',
        message: `Return “${current.title}” for revision?`,
        confirmLabel: 'Return',
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
            ? this.awpService.submit(current.id)
            : action === 'approve'
              ? this.awpService.approve(current.id)
              : action === 'activate'
                ? this.awpService.activate(current.id)
                : action === 'archive'
                  ? this.awpService.archive(current.id)
                  : this.awpService.returnPlan(current.id);
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
    void this.router.navigate(['/app/strategy/annual-work-plans']);
  }

  canEdit(awp: AnnualWorkPlan): boolean {
    return awp.status === 'draft' || awp.status === 'returned';
  }

  canSubmit(awp: AnnualWorkPlan): boolean {
    return awp.status === 'draft' || awp.status === 'returned';
  }

  canApprove(awp: AnnualWorkPlan): boolean {
    return awp.status === 'under_review';
  }

  canReturn(awp: AnnualWorkPlan): boolean {
    return awp.status === 'under_review';
  }

  canActivate(awp: AnnualWorkPlan): boolean {
    return awp.status === 'approved';
  }

  canArchive(awp: AnnualWorkPlan): boolean {
    return awp.status === 'active' || awp.status === 'approved';
  }

  hasApprovalHistory(awp: AnnualWorkPlan): boolean {
    return Array.isArray(awp.approval_history) && awp.approval_history.length > 0;
  }

  activityCount(awp: AnnualWorkPlan): number {
    return flattenActivities(awp).length;
  }
}
