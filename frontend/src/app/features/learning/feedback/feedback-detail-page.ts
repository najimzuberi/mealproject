import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTabsModule } from '@angular/material/tabs';
import {
  FeedbackCase,
  isSensitiveConfidentiality,
  statusLabel,
} from '../../../core/models/learning.model';
import {
  FeedbackAssignPayload,
  FeedbackEscalatePayload,
  FeedbackResolvePayload,
  FeedbackService,
} from '../../../core/services/feedback.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';
import {
  ConfirmDialogComponent,
  ConfirmDialogData,
} from '../../../shared/components/confirm-dialog/confirm-dialog';

@Component({
  selector: 'app-feedback-assign-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
  ],
  template: `
    <h2 mat-dialog-title>Assign case</h2>
    <mat-dialog-content>
      <form class="dlg-form" [formGroup]="form" id="assign-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Assignee user ID</mat-label>
          <input matInput type="number" formControlName="assigned_to" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Department ID</mat-label>
          <input matInput type="number" formControlName="responsible_department_id" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Comment</mat-label>
          <textarea matInput rows="2" formControlName="comment"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="assign-form">Assign</button>
    </mat-dialog-actions>
  `,
  styles: `
    .dlg-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 24rem);
    }
    mat-form-field {
      width: 100%;
    }
  `,
})
export class FeedbackAssignDialogComponent {
  private readonly dialogRef = inject(MatDialogRef<FeedbackAssignDialogComponent, FeedbackAssignPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.group({
    assigned_to: [null as number | null, Validators.required],
    responsible_department_id: [null as number | null],
    comment: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      assigned_to: value.assigned_to != null ? Number(value.assigned_to) : null,
      responsible_department_id:
        value.responsible_department_id != null ? Number(value.responsible_department_id) : null,
      comment: value.comment || null,
    });
  }
}

@Component({
  selector: 'app-feedback-escalate-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
  ],
  template: `
    <h2 mat-dialog-title>Escalate case</h2>
    <mat-dialog-content>
      <form class="dlg-form" [formGroup]="form" id="escalate-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Escalation reason</mat-label>
          <textarea matInput rows="3" formControlName="escalation_reason"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Supervisor user ID</mat-label>
          <input matInput type="number" formControlName="supervisor_id" />
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="escalate-form">Escalate</button>
    </mat-dialog-actions>
  `,
  styles: `
    .dlg-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 24rem);
    }
    mat-form-field {
      width: 100%;
    }
  `,
})
export class FeedbackEscalateDialogComponent {
  private readonly dialogRef = inject(
    MatDialogRef<FeedbackEscalateDialogComponent, FeedbackEscalatePayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.group({
    escalation_reason: ['', Validators.required],
    supervisor_id: [null as number | null],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      escalation_reason: value.escalation_reason,
      supervisor_id: value.supervisor_id != null ? Number(value.supervisor_id) : null,
    });
  }
}

@Component({
  selector: 'app-feedback-resolve-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
  ],
  template: `
    <h2 mat-dialog-title>Resolve case</h2>
    <mat-dialog-content>
      <form class="dlg-form" [formGroup]="form" id="resolve-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Resolution description</mat-label>
          <textarea matInput rows="3" formControlName="resolution_description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Action taken</mat-label>
          <textarea matInput rows="2" formControlName="action_taken"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Feedback loop status</mat-label>
          <input matInput formControlName="feedback_loop_status" />
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="resolve-form">Resolve</button>
    </mat-dialog-actions>
  `,
  styles: `
    .dlg-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 24rem);
    }
    mat-form-field {
      width: 100%;
    }
  `,
})
export class FeedbackResolveDialogComponent {
  private readonly dialogRef = inject(
    MatDialogRef<FeedbackResolveDialogComponent, FeedbackResolvePayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    resolution_description: ['', Validators.required],
    action_taken: [''],
    feedback_loop_status: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      resolution_description: value.resolution_description,
      action_taken: value.action_taken || null,
      feedback_loop_status: value.feedback_loop_status || null,
    });
  }
}

@Component({
  selector: 'app-feedback-detail-page',
  standalone: true,
  imports: [
    DatePipe,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatSnackBarModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './feedback-detail-page.html',
  styleUrl: './feedback-detail-page.scss',
})
export class FeedbackDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly feedbackService = inject(FeedbackService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly feedbackCase = signal<FeedbackCase | null>(null);
  readonly statusLabel = statusLabel;
  readonly isSensitiveConfidentiality = isSensitiveConfidentiality;

  private caseId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid feedback case.');
      this.loading.set(false);
      return;
    }
    this.caseId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.feedbackService.get(this.caseId).subscribe({
      next: (item) => {
        this.feedbackCase.set(item);
        this.loading.set(false);
      },
      error: (err) => {
        const message =
          err?.status === 403
            ? 'You are not authorized to view this case.'
            : 'Unable to load feedback case.';
        this.error.set(message);
        this.loading.set(false);
      },
    });
  }

  goBack(): void {
    void this.router.navigate(['/app/learning/feedback']);
  }

  canAssign(item: FeedbackCase): boolean {
    return item.status === 'received' || item.status === 'assigned';
  }

  canEscalate(item: FeedbackCase): boolean {
    return (
      item.status === 'assigned' ||
      item.status === 'under_investigation' ||
      item.status === 'received'
    );
  }

  canResolve(item: FeedbackCase): boolean {
    return (
      item.status === 'assigned' ||
      item.status === 'under_investigation' ||
      item.status === 'escalated'
    );
  }

  canClose(item: FeedbackCase): boolean {
    return item.status === 'resolved' || item.status === 'referred';
  }

  assign(): void {
    const current = this.feedbackCase();
    if (!current) {
      return;
    }
    this.dialog
      .open(FeedbackAssignDialogComponent, { width: '420px' })
      .afterClosed()
      .subscribe((payload?: FeedbackAssignPayload) => {
        if (!payload) {
          return;
        }
        this.feedbackService.assign(current.id, payload).subscribe({
          next: (saved) => {
            this.feedbackCase.set(saved);
            this.snackBar.open('Case assigned.', 'Close', { duration: 3000 });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Assign failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  escalate(): void {
    const current = this.feedbackCase();
    if (!current) {
      return;
    }
    this.dialog
      .open(FeedbackEscalateDialogComponent, { width: '420px' })
      .afterClosed()
      .subscribe((payload?: FeedbackEscalatePayload) => {
        if (!payload) {
          return;
        }
        this.feedbackService.escalate(current.id, payload).subscribe({
          next: (saved) => {
            this.feedbackCase.set(saved);
            this.snackBar.open('Case escalated.', 'Close', { duration: 3000 });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Escalate failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  resolve(): void {
    const current = this.feedbackCase();
    if (!current) {
      return;
    }
    this.dialog
      .open(FeedbackResolveDialogComponent, { width: '480px' })
      .afterClosed()
      .subscribe((payload?: FeedbackResolvePayload) => {
        if (!payload) {
          return;
        }
        this.feedbackService.resolve(current.id, payload).subscribe({
          next: (saved) => {
            this.feedbackCase.set(saved);
            this.snackBar.open('Case resolved.', 'Close', { duration: 3000 });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Resolve failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  closeCase(): void {
    const current = this.feedbackCase();
    if (!current) {
      return;
    }
    const data: ConfirmDialogData = {
      title: 'Close case',
      message: `Close “${current.reference_number}”?`,
      confirmLabel: 'Close',
    };
    this.dialog
      .open(ConfirmDialogComponent, { data })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.feedbackService.close(current.id).subscribe({
          next: (saved) => {
            this.feedbackCase.set(saved);
            this.snackBar.open('Case closed.', 'Close', { duration: 3000 });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Close failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }
}
