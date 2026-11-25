import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { IndicatorService } from '../../../core/services/indicator.service';
import {
  Indicator,
  approvalEntries,
  statusLabel,
} from '../../../core/models/meal.model';
import { IndicatorFormDialogComponent } from './indicators-page';
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
  selector: 'app-indicator-detail-page',
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
  templateUrl: './indicator-detail-page.html',
  styleUrl: './indicator-detail-page.scss',
})
export class IndicatorDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly indicatorService = inject(IndicatorService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly indicator = signal<Indicator | null>(null);
  readonly versions = signal<Indicator[]>([]);
  readonly statusLabel = statusLabel;
  readonly approvalEntries = approvalEntries;
  readonly baselineColumns = ['value', 'baseline_date'];
  readonly targetColumns = ['value', 'is_cumulative'];
  readonly disaggColumns = ['dimension', 'label', 'is_required'];

  private indicatorId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid indicator.');
      this.loading.set(false);
      return;
    }
    this.indicatorId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.indicatorService.get(this.indicatorId).subscribe({
      next: (indicator) => {
        this.indicator.set(indicator);
        this.loading.set(false);
        this.loadVersions();
      },
      error: () => {
        this.error.set('Unable to load indicator.');
        this.loading.set(false);
      },
    });
  }

  private loadVersions(): void {
    this.indicatorService.versions(this.indicatorId).subscribe({
      next: (rows) => this.versions.set(rows),
      error: () => this.versions.set([]),
    });
  }

  openEdit(): void {
    const current = this.indicator();
    if (!current) {
      return;
    }
    this.dialog
      .open(IndicatorFormDialogComponent, {
        width: '720px',
        data: { indicator: current },
      })
      .afterClosed()
      .subscribe((payload) => {
        if (!payload) {
          return;
        }
        this.indicatorService.update(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Indicator updated.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  runWorkflow(action: 'submit' | 'approve' | 'retire' | 'return'): void {
    const current = this.indicator();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit for review',
        message: `Submit “${current.name}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve indicator',
        message: `Approve “${current.name}”?`,
        confirmLabel: 'Approve',
      },
      retire: {
        title: 'Retire indicator',
        message: `Retire “${current.name}”? It will no longer be available for new plans.`,
        confirmLabel: 'Retire',
        danger: true,
      },
      return: {
        title: 'Return indicator',
        message: `Return “${current.name}” for revision?`,
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
            ? this.indicatorService.submit(current.id)
            : action === 'approve'
              ? this.indicatorService.approve(current.id)
              : action === 'retire'
                ? this.indicatorService.retire(current.id)
                : this.indicatorService.returnIndicator(current.id);
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
    void this.router.navigate(['/app/meal/indicators']);
  }

  canEdit(indicator: Indicator): boolean {
    return indicator.status === 'draft' || indicator.status === 'returned';
  }

  canSubmit(indicator: Indicator): boolean {
    return indicator.status === 'draft' || indicator.status === 'returned';
  }

  canApprove(indicator: Indicator): boolean {
    return indicator.status === 'under_review';
  }

  canReturn(indicator: Indicator): boolean {
    return indicator.status === 'under_review';
  }

  canRetire(indicator: Indicator): boolean {
    return indicator.status === 'approved';
  }
}
