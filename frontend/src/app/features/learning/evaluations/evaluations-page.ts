import { DatePipe } from '@angular/common';
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
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import {
  EVALUATION_STATUSES,
  EVALUATION_TYPES,
  Evaluation,
  statusLabel,
} from '../../../core/models/learning.model';
import { Programme } from '../../../core/models/programme-project.model';
import { EvaluationPayload, EvaluationService } from '../../../core/services/evaluation.service';
import { ProgrammeService } from '../../../core/services/programme.service';
import { ProjectService } from '../../../core/services/project.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-evaluation-form-dialog',
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
    <h2 mat-dialog-title>New evaluation</h2>
    <mat-dialog-content>
      <form class="learn-form learn-form--wide" [formGroup]="form" id="evaluation-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Type</mat-label>
          <mat-select formControlName="evaluation_type">
            <mat-option [value]="null">—</mat-option>
            @for (type of evaluationTypes; track type) {
              <mat-option [value]="type">{{ statusLabel(type) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Programme</mat-label>
          <mat-select formControlName="programme_id">
            <mat-option [value]="null">—</mat-option>
            @for (programme of data.programmes; track programme.id) {
              <mat-option [value]="programme.id">{{ programme.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Project</mat-label>
          <mat-select formControlName="project_id">
            <mat-option [value]="null">—</mat-option>
            @for (project of data.projects; track project.id) {
              <mat-option [value]="project.id">{{ project.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Starts on</mat-label>
          <input matInput type="date" formControlName="starts_on" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Ends on</mat-label>
          <input matInput type="date" formControlName="ends_on" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Purpose</mat-label>
          <textarea matInput rows="2" formControlName="purpose"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Scope</mat-label>
          <textarea matInput rows="2" formControlName="scope"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="evaluation-form">Create</button>
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
export class EvaluationFormDialogComponent {
  readonly data = inject<{
    programmes: Programme[];
    projects: Array<{ id: number; name: string }>;
  }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<EvaluationFormDialogComponent, EvaluationPayload>);
  private readonly fb = inject(FormBuilder);

  readonly evaluationTypes = EVALUATION_TYPES;
  readonly statusLabel = statusLabel;

  readonly form = this.fb.nonNullable.group({
    code: ['', Validators.required],
    title: ['', Validators.required],
    evaluation_type: [null as string | null],
    programme_id: [null as number | null],
    project_id: [null as number | null],
    starts_on: [''],
    ends_on: [''],
    purpose: [''],
    scope: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      code: value.code,
      title: value.title,
      evaluation_type: value.evaluation_type,
      programme_id: value.programme_id,
      project_id: value.project_id,
      starts_on: value.starts_on || null,
      ends_on: value.ends_on || null,
      purpose: value.purpose || null,
      scope: value.scope || null,
    });
  }
}

@Component({
  selector: 'app-evaluations-page',
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
  templateUrl: './evaluations-page.html',
  styleUrl: './evaluations-page.scss',
})
export class EvaluationsPageComponent {
  private readonly evaluationService = inject(EvaluationService);
  private readonly programmeService = inject(ProgrammeService);
  private readonly projectService = inject(ProjectService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Evaluation[]>([]);
  readonly programmes = signal<Programme[]>([]);
  readonly projects = signal<Array<{ id: number; name: string }>>([]);
  readonly statuses = EVALUATION_STATUSES;
  readonly evaluationTypes = EVALUATION_TYPES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['code', 'title', 'type', 'status', 'period', 'updated', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    evaluation_type: [''],
  });

  constructor() {
    this.loadLookups();
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, evaluation_type } = this.filters.getRawValue();
    this.evaluationService
      .list({
        search: search || undefined,
        status: status || undefined,
        evaluation_type: evaluation_type || undefined,
        per_page: 50,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load evaluations.');
          this.loading.set(false);
        },
      });
  }

  openCreate(): void {
    this.dialog
      .open(EvaluationFormDialogComponent, {
        width: '640px',
        data: {
          programmes: this.programmes(),
          projects: this.projects(),
        },
      })
      .afterClosed()
      .subscribe((payload?: EvaluationPayload) => {
        if (!payload) {
          return;
        }
        this.evaluationService.create(payload).subscribe({
          next: (saved) => {
            this.snackBar.open('Evaluation created.', 'Close', { duration: 3000 });
            void this.router.navigate(['/app/learning/evaluations', saved.id]);
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

  view(row: Evaluation): void {
    void this.router.navigate(['/app/learning/evaluations', row.id]);
  }

  private loadLookups(): void {
    forkJoin({
      programmes: this.programmeService.list({ per_page: 100 }).pipe(
        catchError(() => of({ items: [] as Programme[] })),
      ),
      projects: this.projectService.list({ per_page: 100 }).pipe(
        catchError(() => of({ items: [] as Array<{ id: number; name: string }> })),
      ),
    }).subscribe({
      next: ({ programmes, projects }) => {
        this.programmes.set(programmes.items);
        this.projects.set(projects.items);
      },
    });
  }
}
