import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { HttpErrorResponse } from '@angular/common/http';
import { SubmissionService } from '../../../core/services/submission.service';
import {
  Submission,
  approvalEntries,
  statusLabel,
} from '../../../core/models/meal.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';
import {
  ConfirmDialogComponent,
  ConfirmDialogData,
} from '../../../shared/components/confirm-dialog/confirm-dialog';

@Component({
  selector: 'app-submission-detail-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatFormFieldModule,
    MatInputModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './submission-detail-page.html',
  styleUrl: './submission-detail-page.scss',
})
export class SubmissionDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly submissionService = inject(SubmissionService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly submission = signal<Submission | null>(null);
  readonly statusLabel = statusLabel;
  readonly approvalEntries = approvalEntries;

  readonly editForm = this.fb.nonNullable.group({
    value: [''],
    numerator: [''],
    denominator: [''],
    notes: [''],
  });

  private submissionId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid submission.');
      this.loading.set(false);
      return;
    }
    this.submissionId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.submissionService.getSubmission(this.submissionId).subscribe({
      next: (submission) => {
        this.submission.set(submission);
        this.editForm.patchValue({
          value: submission.value != null ? String(submission.value) : '',
          numerator: submission.numerator != null ? String(submission.numerator) : '',
          denominator: submission.denominator != null ? String(submission.denominator) : '',
          notes: submission.notes ?? '',
        });
        this.loading.set(false);
      },
      error: (err: unknown) => {
        if (err instanceof HttpErrorResponse && err.status === 404) {
          this.error.set('Submission not found — data collection API may not be available yet.');
        } else {
          this.error.set('Unable to load submission.');
        }
        this.loading.set(false);
      },
    });
  }

  saveDraft(): void {
    const current = this.submission();
    if (!current || !this.canEdit(current)) {
      return;
    }
    const value = this.editForm.getRawValue();
    this.submissionService
      .updateSubmission(current.id, {
        value: value.value === '' ? null : Number(value.value),
        numerator: value.numerator === '' ? null : Number(value.numerator),
        denominator: value.denominator === '' ? null : Number(value.denominator),
        notes: value.notes || null,
      })
      .subscribe({
        next: () => {
          this.snackBar.open('Submission saved.', 'Close', { duration: 3000 });
          this.reload();
        },
        error: (err) =>
          this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
      });
  }

  runWorkflow(
    action: 'submit' | 'return' | 'verify' | 'validate' | 'approve' | 'publish',
  ): void {
    const current = this.submission();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit data',
        message: 'Submit this data entry for verification?',
        confirmLabel: 'Submit',
      },
      return: {
        title: 'Return submission',
        message: 'Return this submission for correction?',
        confirmLabel: 'Return',
      },
      verify: {
        title: 'Verify submission',
        message: 'Mark this submission as verified?',
        confirmLabel: 'Verify',
      },
      validate: {
        title: 'Validate submission',
        message: 'Mark this submission as validated?',
        confirmLabel: 'Validate',
      },
      approve: {
        title: 'Approve submission',
        message: 'Approve this submission?',
        confirmLabel: 'Approve',
      },
      publish: {
        title: 'Publish submission',
        message: 'Publish this value for performance reporting?',
        confirmLabel: 'Publish',
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
            ? this.submissionService.submit(current.id)
            : action === 'return'
              ? this.submissionService.returnSubmission(current.id)
              : action === 'verify'
                ? this.submissionService.verify(current.id)
                : action === 'validate'
                  ? this.submissionService.validate(current.id)
                  : action === 'approve'
                    ? this.submissionService.approve(current.id)
                    : this.submissionService.publish(current.id);
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
    void this.router.navigate(['/app/meal/data-collection']);
  }

  canEdit(submission: Submission): boolean {
    return submission.status === 'draft' || submission.status === 'returned';
  }

  canSubmit(submission: Submission): boolean {
    return submission.status === 'draft' || submission.status === 'returned';
  }

  canReturn(submission: Submission): boolean {
    return ['submitted', 'verified', 'validated'].includes(submission.status);
  }

  canVerify(submission: Submission): boolean {
    return submission.status === 'submitted';
  }

  canValidate(submission: Submission): boolean {
    return submission.status === 'verified';
  }

  canApprove(submission: Submission): boolean {
    return submission.status === 'validated';
  }

  canPublish(submission: Submission): boolean {
    return submission.status === 'approved';
  }
}
