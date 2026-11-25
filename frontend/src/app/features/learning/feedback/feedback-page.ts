import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatDialog, MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import {
  CONFIDENTIALITY_LEVELS,
  FEEDBACK_CASE_STATUSES,
  FEEDBACK_TYPES,
  FeedbackCaseSummary,
  FeedbackCategory,
  PRIORITIES,
  statusLabel,
} from '../../../core/models/learning.model';
import { FeedbackCasePayload, FeedbackService } from '../../../core/services/feedback.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-feedback-case-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatCheckboxModule,
  ],
  template: `
    <h2 mat-dialog-title>Register feedback case</h2>
    <mat-dialog-content>
      <form class="learn-form learn-form--wide" [formGroup]="form" id="feedback-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Type</mat-label>
          <mat-select formControlName="feedback_type">
            @for (type of feedbackTypes; track type) {
              <mat-option [value]="type">{{ statusLabel(type) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Category</mat-label>
          <mat-select formControlName="feedback_category_id">
            <mat-option [value]="null">—</mat-option>
            @for (category of data.categories; track category.id) {
              <mat-option [value]="category.id">{{ category.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Priority</mat-label>
          <mat-select formControlName="priority">
            @for (priority of priorities; track priority) {
              <mat-option [value]="priority">{{ statusLabel(priority) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Confidentiality</mat-label>
          <mat-select formControlName="confidentiality_level">
            @for (level of confidentialityLevels; track level) {
              <mat-option [value]="level">{{ statusLabel(level) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Channel</mat-label>
          <input matInput formControlName="submission_channel" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
        <mat-checkbox formControlName="is_anonymous">Anonymous</mat-checkbox>
        @if (!form.controls.is_anonymous.value) {
          <mat-form-field appearance="outline">
            <mat-label>Complainant name</mat-label>
            <input matInput formControlName="complainant_name" />
          </mat-form-field>
          <mat-form-field appearance="outline">
            <mat-label>Contact</mat-label>
            <input matInput formControlName="complainant_contact" />
          </mat-form-field>
        }
        <mat-checkbox formControlName="consent_given">Consent given</mat-checkbox>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="feedback-form">Register</button>
    </mat-dialog-actions>
  `,
  styles: `
    .learn-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
      padding-top: 0.25rem;
    }
    .learn-form--wide {
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
      .learn-form--wide {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class FeedbackCaseFormDialogComponent {
  readonly data = inject<{ categories: FeedbackCategory[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<FeedbackCaseFormDialogComponent, FeedbackCasePayload>);
  private readonly fb = inject(FormBuilder);

  readonly feedbackTypes = FEEDBACK_TYPES;
  readonly priorities = PRIORITIES;
  readonly confidentialityLevels = CONFIDENTIALITY_LEVELS;
  readonly statusLabel = statusLabel;

  readonly form = this.fb.nonNullable.group({
    feedback_type: ['feedback' as string, Validators.required],
    feedback_category_id: [null as number | null],
    priority: ['medium' as string],
    confidentiality_level: ['internal' as string],
    submission_channel: [''],
    description: ['', Validators.required],
    is_anonymous: [false],
    complainant_name: [''],
    complainant_contact: [''],
    consent_given: [false],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      feedback_type: value.feedback_type,
      feedback_category_id: value.feedback_category_id,
      priority: value.priority,
      confidentiality_level: value.confidentiality_level,
      submission_channel: value.submission_channel || null,
      description: value.description,
      is_anonymous: value.is_anonymous,
      complainant_name: value.is_anonymous ? null : value.complainant_name || null,
      complainant_contact: value.is_anonymous ? null : value.complainant_contact || null,
      consent_given: value.consent_given,
    });
  }
}

@Component({
  selector: 'app-feedback-page',
  standalone: true,
  imports: [
    DatePipe,
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
  templateUrl: './feedback-page.html',
  styleUrl: './feedback-page.scss',
})
export class FeedbackPageComponent {
  private readonly feedbackService = inject(FeedbackService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<FeedbackCaseSummary[]>([]);
  readonly categories = signal<FeedbackCategory[]>([]);
  readonly statuses = FEEDBACK_CASE_STATUSES;
  readonly feedbackTypes = FEEDBACK_TYPES;
  readonly statusLabel = statusLabel;

  /** Safe columns only — never complainant PII or restricted bodies. */
  readonly displayedColumns = [
    'reference',
    'type',
    'category',
    'priority',
    'status',
    'channel',
    'submitted',
    'actions',
  ];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    feedback_type: [''],
  });

  constructor() {
    this.loadCategories();
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, feedback_type } = this.filters.getRawValue();
    this.feedbackService
      .list({
        search: search || undefined,
        status: status || undefined,
        feedback_type: feedback_type || undefined,
        per_page: 50,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load feedback cases.');
          this.loading.set(false);
        },
      });
  }

  openCreate(): void {
    this.dialog
      .open(FeedbackCaseFormDialogComponent, {
        width: '640px',
        data: { categories: this.categories() },
      })
      .afterClosed()
      .subscribe((payload?: FeedbackCasePayload) => {
        if (!payload) {
          return;
        }
        this.feedbackService.create(payload).subscribe({
          next: (saved) => {
            this.snackBar.open('Feedback case registered.', 'Close', { duration: 3000 });
            void this.router.navigate(['/app/learning/feedback', saved.id]);
          },
          error: (err) =>
            this.snackBar.open(
              err?.error?.message || 'Create failed (API may be unavailable).',
              'Close',
              { duration: 4000 },
            ),
        });
      });
  }

  view(row: FeedbackCaseSummary): void {
    void this.router.navigate(['/app/learning/feedback', row.id]);
  }

  private loadCategories(): void {
    this.feedbackService.listCategories({ per_page: 100 }).subscribe({
      next: (page) => this.categories.set(page.items),
      error: () => this.categories.set([]),
    });
  }
}
