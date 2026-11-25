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
import { forkJoin } from 'rxjs';
import { DqaAssessmentPayload, DqaService } from '../../../core/services/dqa.service';
import { SubmissionService } from '../../../core/services/submission.service';
import {
  DQA_ASSESSMENT_STATUSES,
  DqaAssessment,
  DqaIssue,
  Submission,
  statusLabel,
} from '../../../core/models/meal.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-dqa-assessment-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
  ],
  template: `
    <h2 mat-dialog-title>New DQA assessment</h2>
    <mat-dialog-content>
      <form class="meal-form" [formGroup]="form" id="dqa-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Submission</mat-label>
          <mat-select formControlName="submission_id">
            @for (submission of data.submissions; track submission.id) {
              <mat-option [value]="submission.id">
                #{{ submission.id }} —
                {{ submission.indicator?.name || ('Indicator #' + submission.indicator_id) }}
                ({{ statusLabel(submission.status) }})
              </mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Summary</mat-label>
          <textarea matInput rows="3" formControlName="summary"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="dqa-form">Create</button>
    </mat-dialog-actions>
  `,
  styles: `
    .meal-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
      padding-top: 0.25rem;
    }
    mat-form-field {
      width: 100%;
    }
  `,
})
export class DqaAssessmentFormDialogComponent {
  readonly data = inject<{ submissions: Submission[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<DqaAssessmentFormDialogComponent, DqaAssessmentPayload>,
  );
  private readonly fb = inject(FormBuilder);
  readonly statusLabel = statusLabel;

  readonly form = this.fb.nonNullable.group({
    submission_id: [null as number | null, Validators.required],
    summary: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      submission_id: Number(value.submission_id),
      summary: value.summary || null,
    });
  }
}

@Component({
  selector: 'app-dqa-page',
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
  templateUrl: './dqa-page.html',
  styleUrl: './dqa-page.scss',
})
export class DqaPageComponent {
  private readonly dqaService = inject(DqaService);
  private readonly submissionService = inject(SubmissionService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly apiUnavailable = signal(false);
  readonly assessments = signal<DqaAssessment[]>([]);
  readonly openIssues = signal<DqaIssue[]>([]);
  readonly submissions = signal<Submission[]>([]);
  readonly statuses = DQA_ASSESSMENT_STATUSES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['id', 'submission', 'overall_score', 'status', 'completed_at', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    status: [''],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { status } = this.filters.getRawValue();
    forkJoin({
      assessments: this.dqaService.listAssessments({ status: status || undefined }),
      issues: this.dqaService.listIssues({ status: 'open' }),
      submissions: this.submissionService.listSubmissions({ per_page: 50 }),
    }).subscribe({
      next: ({ assessments, issues, submissions }) => {
        this.assessments.set(assessments.items);
        this.openIssues.set(issues.items);
        this.submissions.set(submissions.items);
        this.apiUnavailable.set(assessments.items.length === 0 && issues.items.length === 0);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load DQA workspace.');
        this.loading.set(false);
      },
    });
  }

  openCreate(): void {
    this.dialog
      .open(DqaAssessmentFormDialogComponent, {
        width: '480px',
        data: { submissions: this.submissions() },
      })
      .afterClosed()
      .subscribe((payload?: DqaAssessmentPayload) => {
        if (!payload) {
          return;
        }
        this.dqaService.createAssessment(payload).subscribe({
          next: (saved) => {
            this.snackBar.open('Assessment created.', 'Close', { duration: 3000 });
            void this.router.navigate(['/app/meal/dqa', saved.id]);
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed (API may be unavailable).', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  view(row: DqaAssessment): void {
    void this.router.navigate(['/app/meal/dqa', row.id]);
  }
}
