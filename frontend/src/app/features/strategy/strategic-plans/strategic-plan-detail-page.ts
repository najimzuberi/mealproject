import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { StrategicPlanService } from '../../../core/services/strategic-plan.service';
import { StrategicPlan, statusLabel } from '../../../core/models/strategy.model';
import { StrategicPlanFormDialogComponent } from './strategic-plans-page';
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
  selector: 'app-strategic-plan-detail-page',
  standalone: true,
  imports: [
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
  templateUrl: './strategic-plan-detail-page.html',
  styleUrl: './strategic-plan-detail-page.scss',
})
export class StrategicPlanDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly planService = inject(StrategicPlanService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly plan = signal<StrategicPlan | null>(null);
  readonly statusLabel = statusLabel;

  private planId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid strategic plan.');
      this.loading.set(false);
      return;
    }
    this.planId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.planService.get(this.planId).subscribe({
      next: (plan) => {
        this.plan.set(plan);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load strategic plan.');
        this.loading.set(false);
      },
    });
  }

  openEdit(): void {
    const current = this.plan();
    if (!current) {
      return;
    }
    this.dialog
      .open(StrategicPlanFormDialogComponent, {
        width: '640px',
        data: { plan: current },
      })
      .afterClosed()
      .subscribe((payload) => {
        if (!payload) {
          return;
        }
        this.planService.update(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Strategic plan updated.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  runWorkflow(action: 'submit' | 'approve' | 'activate' | 'archive' | 'return'): void {
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
      approve: {
        title: 'Approve plan',
        message: `Approve “${current.title}”?`,
        confirmLabel: 'Approve',
      },
      activate: {
        title: 'Activate plan',
        message: `Activate “${current.title}”?`,
        confirmLabel: 'Activate',
      },
      archive: {
        title: 'Archive plan',
        message: `Archive “${current.title}”?`,
        confirmLabel: 'Archive',
      },
      return: {
        title: 'Return plan',
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
            ? this.planService.submit(current.id)
            : action === 'approve'
              ? this.planService.approve(current.id)
              : action === 'activate'
                ? this.planService.activate(current.id)
                : action === 'archive'
                  ? this.planService.archive(current.id)
                  : this.planService.returnPlan(current.id);
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
    void this.router.navigate(['/app/strategy/strategic-plans']);
  }

  canEdit(plan: StrategicPlan): boolean {
    return plan.status === 'draft' || plan.status === 'returned';
  }

  canSubmit(plan: StrategicPlan): boolean {
    return plan.status === 'draft' || plan.status === 'returned';
  }

  canApprove(plan: StrategicPlan): boolean {
    return plan.status === 'under_review';
  }

  canReturn(plan: StrategicPlan): boolean {
    return plan.status === 'under_review';
  }

  canActivate(plan: StrategicPlan): boolean {
    return plan.status === 'approved';
  }

  canArchive(plan: StrategicPlan): boolean {
    return plan.status === 'active' || plan.status === 'approved';
  }

  hasApprovalHistory(plan: StrategicPlan): boolean {
    return Array.isArray(plan.approval_history) && plan.approval_history.length > 0;
  }

  hasVersions(plan: StrategicPlan): boolean {
    return Array.isArray(plan.versions) && plan.versions.length > 0;
  }
}
