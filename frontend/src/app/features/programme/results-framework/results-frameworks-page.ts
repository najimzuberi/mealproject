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
import { MatTooltipModule } from '@angular/material/tooltip';
import {
  ResultsFrameworkPayload,
  ResultsFrameworkService,
} from '../../../core/services/results-framework.service';
import { ProjectService } from '../../../core/services/project.service';
import {
  Project,
  RESULTS_FRAMEWORK_STATUSES,
  ResultsFramework,
  statusLabel,
} from '../../../core/models/programme-project.model';
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
  selector: 'app-results-framework-form-dialog',
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
    <h2 mat-dialog-title>{{ data.framework ? 'Edit results framework' : 'Create results framework' }}</h2>
    <mat-dialog-content>
      <form class="programme-form" [formGroup]="form" id="rf-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Project</mat-label>
          <mat-select formControlName="project_id">
            @for (project of data.projects; track project.id) {
              <mat-option [value]="project.id">{{ project.code }} — {{ project.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Theory of change</mat-label>
          <textarea matInput rows="4" formControlName="theory_of_change"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="rf-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .programme-form {
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
export class ResultsFrameworkFormDialogComponent {
  readonly data = inject<{
    framework: ResultsFramework | null;
    projects: Project[];
    defaultProjectId?: number | null;
  }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<ResultsFrameworkFormDialogComponent, ResultsFrameworkPayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.group({
    project_id: [
      this.data.framework?.project_id ?? this.data.defaultProjectId ?? null as number | null,
      Validators.required,
    ],
    title: [this.data.framework?.title ?? '', Validators.required],
    theory_of_change: [this.data.framework?.theory_of_change ?? ''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      project_id: Number(value.project_id),
      title: value.title!,
      theory_of_change: value.theory_of_change || null,
    });
  }
}

@Component({
  selector: 'app-results-frameworks-page',
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
  templateUrl: './results-frameworks-page.html',
  styleUrl: './results-frameworks-page.scss',
})
export class ResultsFrameworksPageComponent {
  private readonly rfService = inject(ResultsFrameworkService);
  private readonly projectService = inject(ProjectService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<ResultsFramework[]>([]);
  readonly projects = signal<Project[]>([]);
  readonly statuses = RESULTS_FRAMEWORK_STATUSES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['title', 'project', 'status', 'version', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    project_id: [''],
  });

  private defaultProjectId: number | null = null;

  constructor() {
    const qpProject = this.route.snapshot.queryParamMap.get('project');
    if (qpProject) {
      this.defaultProjectId = Number(qpProject) || null;
      this.filters.patchValue({ project_id: String(this.defaultProjectId || '') });
    }
    this.projectService.list({ per_page: 100 }).subscribe({
      next: (page) => this.projects.set(page.items),
    });
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, project_id } = this.filters.getRawValue();
    const projectIdNum = project_id ? Number(project_id) : undefined;
    this.rfService
      .list({
        search: search || undefined,
        status: status || undefined,
        project_id: projectIdNum,
      })
      .subscribe({
        next: (page) => {
          let rows = page.items;
          if (search) {
            const q = search.toLowerCase();
            rows = rows.filter((f) => f.title.toLowerCase().includes(q));
          }
          if (status) {
            rows = rows.filter((f) => f.status === status);
          }
          if (projectIdNum) {
            rows = rows.filter((f) => f.project_id === projectIdNum);
          }
          this.items.set(rows);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load results frameworks.');
          this.loading.set(false);
        },
      });
  }

  projectName(framework: ResultsFramework): string {
    if (framework.project?.name) {
      return framework.project.name;
    }
    return this.projects().find((p) => p.id === framework.project_id)?.name ?? `#${framework.project_id}`;
  }

  openForm(framework: ResultsFramework | null = null): void {
    this.dialog
      .open(ResultsFrameworkFormDialogComponent, {
        width: '520px',
        data: {
          framework,
          projects: this.projects(),
          defaultProjectId: this.defaultProjectId,
        },
      })
      .afterClosed()
      .subscribe((payload?: ResultsFrameworkPayload) => {
        if (!payload) {
          return;
        }
        const request$ = framework
          ? this.rfService.update(framework.id, payload)
          : this.rfService.create(payload);
        request$.subscribe({
          next: (saved) => {
            this.snackBar.open(
              framework ? 'Framework updated.' : 'Framework created.',
              'Close',
              { duration: 3000 },
            );
            if (!framework) {
              void this.router.navigate(['/app/programme/results-frameworks', saved.id]);
              return;
            }
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  view(framework: ResultsFramework): void {
    void this.router.navigate(['/app/programme/results-frameworks', framework.id]);
  }

  runWorkflow(
    framework: ResultsFramework,
    action: 'submit' | 'approve' | 'activate' | 'archive',
  ): void {
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit for review',
        message: `Submit “${framework.title}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve framework',
        message: `Approve “${framework.title}”?`,
        confirmLabel: 'Approve',
      },
      activate: {
        title: 'Activate framework',
        message: `Activate “${framework.title}”?`,
        confirmLabel: 'Activate',
      },
      archive: {
        title: 'Archive framework',
        message: `Archive “${framework.title}”?`,
        confirmLabel: 'Archive',
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
            ? this.rfService.submit(framework.id)
            : action === 'approve'
              ? this.rfService.approve(framework.id)
              : action === 'activate'
                ? this.rfService.activate(framework.id)
                : this.rfService.archive(framework.id);
        request$.subscribe({
          next: () => {
            this.snackBar.open('Workflow action completed.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || `${action} failed.`, 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  canEdit(framework: ResultsFramework): boolean {
    return framework.status === 'draft' || framework.status === 'returned';
  }

  canSubmit(framework: ResultsFramework): boolean {
    return framework.status === 'draft' || framework.status === 'returned';
  }

  canApprove(framework: ResultsFramework): boolean {
    return framework.status === 'under_review';
  }

  canActivate(framework: ResultsFramework): boolean {
    return framework.status === 'approved';
  }

  canArchive(framework: ResultsFramework): boolean {
    return framework.status === 'active' || framework.status === 'approved';
  }
}
