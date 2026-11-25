import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import {
  StrategicPlanPayload,
  StrategicPlanService,
} from '../../../core/services/strategic-plan.service';
import { StrategicPlan, STRATEGY_STATUSES, statusLabel } from '../../../core/models/strategy.model';
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
  selector: 'app-strategic-plan-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
  ],
  template: `
    <h2 mat-dialog-title>{{ data.plan ? 'Edit strategic plan' : 'Create strategic plan' }}</h2>
    <mat-dialog-content>
      <form class="strategy-form strategy-form--wide" [formGroup]="form" id="plan-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Start date</mat-label>
          <input matInput type="date" formControlName="start_date" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>End date</mat-label>
          <input matInput type="date" formControlName="end_date" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Vision</mat-label>
          <textarea matInput rows="2" formControlName="vision"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Mission</mat-label>
          <textarea matInput rows="2" formControlName="mission"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="plan-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .strategy-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
      padding-top: 0.25rem;
    }
    .strategy-form--wide {
      min-width: min(100%, 40rem);
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem 1rem;
    }
    .full-width {
      grid-column: 1 / -1;
    }
    mat-form-field {
      width: 100%;
    }
    @media (max-width: 720px) {
      .strategy-form--wide {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class StrategicPlanFormDialogComponent {
  readonly data = inject<{ plan: StrategicPlan | null }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<StrategicPlanFormDialogComponent, StrategicPlanPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    title: [this.data.plan?.title ?? '', Validators.required],
    code: [this.data.plan?.code ?? '', Validators.required],
    start_date: [this.data.plan?.start_date ?? '', Validators.required],
    end_date: [this.data.plan?.end_date ?? '', Validators.required],
    vision: [this.data.plan?.vision ?? ''],
    mission: [this.data.plan?.mission ?? ''],
    description: [this.data.plan?.description ?? ''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      ...value,
      vision: value.vision || null,
      mission: value.mission || null,
      description: value.description || null,
    });
  }
}

@Component({
  selector: 'app-strategic-plans-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    RouterLink,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
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
  templateUrl: './strategic-plans-page.html',
  styleUrl: './strategic-plans-page.scss',
})
export class StrategicPlansPageComponent {
  private readonly planService = inject(StrategicPlanService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<StrategicPlan[]>([]);
  readonly statuses = STRATEGY_STATUSES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['title', 'code', 'period', 'status', 'version', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status } = this.filters.getRawValue();
    this.planService
      .list({
        search: search || undefined,
        status: status || undefined,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load strategic plans.');
          this.loading.set(false);
        },
      });
  }

  openForm(plan: StrategicPlan | null = null): void {
    this.dialog
      .open(StrategicPlanFormDialogComponent, {
        width: '640px',
        data: { plan },
      })
      .afterClosed()
      .subscribe((payload?: StrategicPlanPayload) => {
        if (!payload) {
          return;
        }
        const request$ = plan
          ? this.planService.update(plan.id, payload)
          : this.planService.create(payload);
        request$.subscribe({
          next: (saved) => {
            this.snackBar.open(plan ? 'Strategic plan updated.' : 'Strategic plan created.', 'Close', {
              duration: 3000,
            });
            if (!plan) {
              void this.router.navigate(['/app/strategy/strategic-plans', saved.id]);
              return;
            }
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  view(plan: StrategicPlan): void {
    void this.router.navigate(['/app/strategy/strategic-plans', plan.id]);
  }

  runWorkflow(
    plan: StrategicPlan,
    action: 'submit' | 'approve' | 'activate' | 'archive',
  ): void {
    const labels: Record<typeof action, { title: string; message: string; confirmLabel: string }> = {
      submit: {
        title: 'Submit for review',
        message: `Submit “${plan.title}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve plan',
        message: `Approve “${plan.title}”?`,
        confirmLabel: 'Approve',
      },
      activate: {
        title: 'Activate plan',
        message: `Activate “${plan.title}”? This may archive other active plans.`,
        confirmLabel: 'Activate',
      },
      archive: {
        title: 'Archive plan',
        message: `Archive “${plan.title}”?`,
        confirmLabel: 'Archive',
      },
    };
    const data: ConfirmDialogData = labels[action];
    this.dialog
      .open(ConfirmDialogComponent, { data })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        const request$ =
          action === 'submit'
            ? this.planService.submit(plan.id)
            : action === 'approve'
              ? this.planService.approve(plan.id)
              : action === 'activate'
                ? this.planService.activate(plan.id)
                : this.planService.archive(plan.id);
        request$.subscribe({
          next: () => {
            const messages = {
              submit: 'Plan submitted.',
              approve: 'Plan approved.',
              activate: 'Plan activated.',
              archive: 'Plan archived.',
            } as const;
            this.snackBar.open(messages[action], 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || `${action} failed.`, 'Close', {
              duration: 4000,
            }),
        });
      });
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

  canActivate(plan: StrategicPlan): boolean {
    return plan.status === 'approved';
  }

  canArchive(plan: StrategicPlan): boolean {
    return plan.status === 'active' || plan.status === 'approved';
  }
}
