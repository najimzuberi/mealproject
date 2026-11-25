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
import { LESSON_STATUSES, Lesson, statusLabel } from '../../../core/models/learning.model';
import { Programme } from '../../../core/models/programme-project.model';
import { LessonPayload, LessonService } from '../../../core/services/lesson.service';
import { ProgrammeService } from '../../../core/services/programme.service';
import { ProjectService } from '../../../core/services/project.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-lesson-form-dialog',
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
    <h2 mat-dialog-title>Capture lesson</h2>
    <mat-dialog-content>
      <form class="learn-form learn-form--wide" [formGroup]="form" id="lesson-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Reference</mat-label>
          <input matInput formControlName="reference" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Source</mat-label>
          <input matInput formControlName="source" />
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
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description</mat-label>
          <textarea matInput rows="2" formControlName="description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>What worked</mat-label>
          <textarea matInput rows="2" formControlName="what_worked"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>What did not work</mat-label>
          <textarea matInput rows="2" formControlName="what_did_not_work"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Recommendation</mat-label>
          <textarea matInput rows="2" formControlName="recommendation"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="lesson-form">Create</button>
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
export class LessonFormDialogComponent {
  readonly data = inject<{
    programmes: Programme[];
    projects: Array<{ id: number; name: string }>;
  }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<LessonFormDialogComponent, LessonPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    reference: ['', Validators.required],
    title: ['', Validators.required],
    source: [''],
    programme_id: [null as number | null],
    project_id: [null as number | null],
    description: [''],
    what_worked: [''],
    what_did_not_work: [''],
    recommendation: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      reference: value.reference,
      title: value.title,
      source: value.source || null,
      programme_id: value.programme_id,
      project_id: value.project_id,
      description: value.description || null,
      what_worked: value.what_worked || null,
      what_did_not_work: value.what_did_not_work || null,
      recommendation: value.recommendation || null,
    });
  }
}

@Component({
  selector: 'app-lessons-page',
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
  templateUrl: './lessons-page.html',
  styleUrl: './lessons-page.scss',
})
export class LessonsPageComponent {
  private readonly lessonService = inject(LessonService);
  private readonly programmeService = inject(ProgrammeService);
  private readonly projectService = inject(ProjectService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Lesson[]>([]);
  readonly programmes = signal<Programme[]>([]);
  readonly projects = signal<Array<{ id: number; name: string }>>([]);
  readonly statuses = LESSON_STATUSES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['reference', 'title', 'source', 'status', 'updated', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
  });

  constructor() {
    this.loadLookups();
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status } = this.filters.getRawValue();
    this.lessonService
      .list({
        search: search || undefined,
        status: status || undefined,
        per_page: 50,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load lessons.');
          this.loading.set(false);
        },
      });
  }

  openCreate(): void {
    this.dialog
      .open(LessonFormDialogComponent, {
        width: '640px',
        data: {
          programmes: this.programmes(),
          projects: this.projects(),
        },
      })
      .afterClosed()
      .subscribe((payload?: LessonPayload) => {
        if (!payload) {
          return;
        }
        this.lessonService.create(payload).subscribe({
          next: (saved) => {
            this.snackBar.open('Lesson captured.', 'Close', { duration: 3000 });
            void this.router.navigate(['/app/learning/lessons', saved.id]);
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

  view(row: Lesson): void {
    void this.router.navigate(['/app/learning/lessons', row.id]);
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
