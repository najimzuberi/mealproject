import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { HttpErrorResponse } from '@angular/common/http';
import { DqaIssuePayload, DqaService } from '../../../core/services/dqa.service';
import {
  DqaAssessment,
  DqaIssue,
  statusLabel,
} from '../../../core/models/meal.model';
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
  selector: 'app-dqa-issue-form-dialog',
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
    <h2 mat-dialog-title>Log DQA issue</h2>
    <mat-dialog-content>
      <form class="meal-form" [formGroup]="form" id="issue-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Dimension</mat-label>
          <mat-select formControlName="dimension">
            <mat-option value="">—</mat-option>
            @for (dim of dimensions; track dim) {
              <mat-option [value]="dim">{{ statusLabel(dim) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Severity</mat-label>
          <mat-select formControlName="severity">
            <mat-option value="low">Low</mat-option>
            <mat-option value="medium">Medium</mat-option>
            <mat-option value="high">High</mat-option>
            <mat-option value="critical">Critical</mat-option>
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="issue-form">Save</button>
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
export class DqaIssueFormDialogComponent {
  readonly data = inject<{ assessment: DqaAssessment }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<DqaIssueFormDialogComponent, DqaIssuePayload>);
  private readonly fb = inject(FormBuilder);
  readonly statusLabel = statusLabel;
  readonly dimensions = [
    'validity',
    'reliability',
    'completeness',
    'timeliness',
    'precision',
    'integrity',
  ];

  readonly form = this.fb.nonNullable.group({
    title: ['', Validators.required],
    dimension: [''],
    severity: ['medium'],
    description: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      dqa_assessment_id: this.data.assessment.id,
      submission_id: this.data.assessment.submission_id,
      title: value.title,
      dimension: value.dimension || null,
      severity: value.severity || null,
      description: value.description || null,
    });
  }
}

@Component({
  selector: 'app-assessment-detail-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    RouterLink,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatTableModule,
    MatFormFieldModule,
    MatInputModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './assessment-detail-page.html',
  styleUrl: './assessment-detail-page.scss',
})
export class AssessmentDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly dqaService = inject(DqaService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly assessment = signal<DqaAssessment | null>(null);
  readonly issues = signal<DqaIssue[]>([]);
  readonly statusLabel = statusLabel;
  readonly issueColumns = ['title', 'dimension', 'severity', 'status', 'actions'];

  readonly scoreForm = this.fb.nonNullable.group({
    validity_score: [null as number | null],
    reliability_score: [null as number | null],
    completeness_score: [null as number | null],
    timeliness_score: [null as number | null],
    precision_score: [null as number | null],
    integrity_score: [null as number | null],
    summary: [''],
  });

  private assessmentId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid assessment.');
      this.loading.set(false);
      return;
    }
    this.assessmentId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.dqaService.getAssessment(this.assessmentId).subscribe({
      next: (assessment) => {
        this.assessment.set(assessment);
        this.scoreForm.patchValue({
          validity_score: assessment.validity_score ?? null,
          reliability_score: assessment.reliability_score ?? null,
          completeness_score: assessment.completeness_score ?? null,
          timeliness_score: assessment.timeliness_score ?? null,
          precision_score: assessment.precision_score ?? null,
          integrity_score: assessment.integrity_score ?? null,
          summary: assessment.summary ?? '',
        });
        if (assessment.issues?.length) {
          this.issues.set(assessment.issues);
        } else {
          this.loadIssues();
        }
        this.loading.set(false);
      },
      error: (err: unknown) => {
        if (err instanceof HttpErrorResponse && err.status === 404) {
          this.error.set('Assessment not found — DQA API may not be available yet.');
        } else {
          this.error.set('Unable to load assessment.');
        }
        this.loading.set(false);
      },
    });
  }

  private loadIssues(): void {
    this.dqaService.listIssues({}).subscribe({
      next: (page) =>
        this.issues.set(page.items.filter((i) => i.dqa_assessment_id === this.assessmentId)),
      error: () => this.issues.set([]),
    });
  }

  completeAssessment(): void {
    const current = this.assessment();
    if (!current || !this.canComplete(current)) {
      return;
    }
    const scores = this.scoreForm.getRawValue();
    const data: ConfirmDialogData = {
      title: 'Complete assessment',
      message: 'Mark this DQA as completed with the current scores?',
      confirmLabel: 'Complete',
    };
    this.dialog
      .open(ConfirmDialogComponent, { data })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.dqaService
          .complete(current.id, {
            ...scores,
            summary: scores.summary || null,
          })
          .subscribe({
            next: () => {
              this.snackBar.open('Assessment completed.', 'Close', { duration: 3000 });
              this.reload();
            },
            error: (err) =>
              this.snackBar.open(err?.error?.message || 'Action failed.', 'Close', {
                duration: 4000,
              }),
          });
      });
  }

  openIssueForm(): void {
    const current = this.assessment();
    if (!current) {
      return;
    }
    this.dialog
      .open(DqaIssueFormDialogComponent, {
        width: '480px',
        data: { assessment: current },
      })
      .afterClosed()
      .subscribe((payload?: DqaIssuePayload) => {
        if (!payload) {
          return;
        }
        this.dqaService.createIssue(payload).subscribe({
          next: () => {
            this.snackBar.open('Issue logged.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  resolveIssue(issue: DqaIssue): void {
    const data: ConfirmDialogData = {
      title: 'Resolve issue',
      message: `Mark “${issue.title}” as resolved?`,
      confirmLabel: 'Resolve',
    };
    this.dialog
      .open(ConfirmDialogComponent, { data })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.dqaService.resolveIssue(issue.id, { status: 'resolved' }).subscribe({
          next: () => {
            this.snackBar.open('Issue resolved.', 'Close', { duration: 3000 });
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
    void this.router.navigate(['/app/meal/dqa']);
  }

  canComplete(assessment: DqaAssessment): boolean {
    return assessment.status === 'draft' || assessment.status === 'in_progress';
  }

  canAddIssue(assessment: DqaAssessment): boolean {
    return assessment.status !== 'closed';
  }

  scoreCards(assessment: DqaAssessment): Array<{ label: string; value: number | string | null | undefined }> {
    return [
      { label: 'Validity', value: assessment.validity_score },
      { label: 'Reliability', value: assessment.reliability_score },
      { label: 'Completeness', value: assessment.completeness_score },
      { label: 'Timeliness', value: assessment.timeliness_score },
      { label: 'Precision', value: assessment.precision_score },
      { label: 'Integrity', value: assessment.integrity_score },
      { label: 'Overall', value: assessment.overall_score },
    ];
  }
}
