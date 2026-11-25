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
import { ProjectPayload, ProjectService } from '../../../core/services/project.service';
import { ProgrammeService } from '../../../core/services/programme.service';
import { StrategicPlanService } from '../../../core/services/strategic-plan.service';
import {
  Programme,
  Project,
  PROJECT_STATUSES,
  formatBudget,
  statusLabel,
} from '../../../core/models/programme-project.model';
import { StrategicPlan } from '../../../core/models/strategy.model';
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
  selector: 'app-project-form-dialog',
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
    <h2 mat-dialog-title>{{ data.project ? 'Edit project' : 'Create project' }}</h2>
    <mat-dialog-content>
      <form class="programme-form programme-form--wide" [formGroup]="form" id="project-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Programme</mat-label>
          <mat-select formControlName="programme_id">
            <mat-option [value]="null">None</mat-option>
            @for (prog of data.programmes; track prog.id) {
              <mat-option [value]="prog.id">{{ prog.code }} — {{ prog.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Name</mat-label>
          <input matInput formControlName="name" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Start date</mat-label>
          <input matInput type="date" formControlName="starts_on" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>End date</mat-label>
          <input matInput type="date" formControlName="ends_on" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Budget</mat-label>
          <input matInput type="number" formControlName="budget" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Strategic plan</mat-label>
          <mat-select formControlName="strategic_plan_id">
            <mat-option [value]="null">None</mat-option>
            @for (plan of data.plans; track plan.id) {
              <mat-option [value]="plan.id">{{ plan.code }} — {{ plan.title }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="project-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .programme-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
      padding-top: 0.25rem;
    }
    .programme-form--wide {
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
      .programme-form--wide {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class ProjectFormDialogComponent {
  readonly data = inject<{
    project: Project | null;
    programmes: Programme[];
    plans: StrategicPlan[];
    defaultProgrammeId?: number | null;
  }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<ProjectFormDialogComponent, ProjectPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.group({
    programme_id: [this.data.project?.programme_id ?? this.data.defaultProgrammeId ?? null as number | null],
    code: [this.data.project?.code ?? '', Validators.required],
    name: [this.data.project?.name ?? '', Validators.required],
    starts_on: [this.data.project?.starts_on ?? '', Validators.required],
    ends_on: [this.data.project?.ends_on ?? '', Validators.required],
    budget: [this.data.project?.budget != null ? Number(this.data.project.budget) : null as number | null],
    strategic_plan_id: [this.data.project?.strategic_plan_id ?? null as number | null],
    description: [this.data.project?.description ?? ''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      programme_id: value.programme_id,
      code: value.code!,
      name: value.name!,
      starts_on: value.starts_on!,
      ends_on: value.ends_on!,
      budget: value.budget,
      strategic_plan_id: value.strategic_plan_id,
      description: value.description || null,
    });
  }
}

@Component({
  selector: 'app-projects-page',
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
  templateUrl: './projects-page.html',
  styleUrl: './projects-page.scss',
})
export class ProjectsPageComponent {
  private readonly projectService = inject(ProjectService);
  private readonly programmeService = inject(ProgrammeService);
  private readonly planService = inject(StrategicPlanService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Project[]>([]);
  readonly programmes = signal<Programme[]>([]);
  readonly plans = signal<StrategicPlan[]>([]);
  readonly statuses = PROJECT_STATUSES;
  readonly statusLabel = statusLabel;
  readonly formatBudget = formatBudget;
  readonly displayedColumns = ['name', 'code', 'programme', 'period', 'budget', 'status', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    programme_id: [''],
  });

  private defaultProgrammeId: number | null = null;

  constructor() {
    const qpProgramme = this.route.snapshot.queryParamMap.get('programme');
    if (qpProgramme) {
      this.defaultProgrammeId = Number(qpProgramme) || null;
      this.filters.patchValue({ programme_id: String(this.defaultProgrammeId || '') });
    }
    this.loadLookups();
    this.reload();
  }

  private loadLookups(): void {
    this.programmeService.list({ per_page: 100 }).subscribe({
      next: (page) => this.programmes.set(page.items),
    });
    this.planService.list({ per_page: 100 }).subscribe({
      next: (page) => this.plans.set(page.items),
    });
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, programme_id } = this.filters.getRawValue();
    const programmeIdNum = programme_id ? Number(programme_id) : undefined;
    this.projectService
      .list({
        search: search || undefined,
        status: status || undefined,
        programme_id: programmeIdNum,
      })
      .subscribe({
        next: (page) => {
          let rows = page.items;
          if (search) {
            const q = search.toLowerCase();
            rows = rows.filter(
              (p) =>
                p.name.toLowerCase().includes(q) ||
                (p.code || '').toLowerCase().includes(q),
            );
          }
          if (status) {
            rows = rows.filter((p) => p.status === status);
          }
          if (programmeIdNum) {
            rows = rows.filter((p) => p.programme_id === programmeIdNum);
          }
          this.items.set(rows);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load projects.');
          this.loading.set(false);
        },
      });
  }

  programmeName(project: Project): string {
    if (project.programme?.name) {
      return project.programme.name;
    }
    if (!project.programme_id) {
      return '—';
    }
    return this.programmes().find((p) => p.id === project.programme_id)?.name ?? `#${project.programme_id}`;
  }

  openForm(project: Project | null = null): void {
    this.dialog
      .open(ProjectFormDialogComponent, {
        width: '640px',
        data: {
          project,
          programmes: this.programmes(),
          plans: this.plans(),
          defaultProgrammeId: this.defaultProgrammeId,
        },
      })
      .afterClosed()
      .subscribe((payload?: ProjectPayload) => {
        if (!payload) {
          return;
        }
        const request$ = project
          ? this.projectService.update(project.id, payload)
          : this.projectService.create(payload);
        request$.subscribe({
          next: (saved) => {
            this.snackBar.open(project ? 'Project updated.' : 'Project created.', 'Close', {
              duration: 3000,
            });
            if (!project) {
              void this.router.navigate(['/app/programme/projects', saved.id]);
              return;
            }
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  view(project: Project): void {
    void this.router.navigate(['/app/programme/projects', project.id]);
  }

  runWorkflow(project: Project, action: 'activate' | 'archive' | 'submit' | 'approve'): void {
    const labels: Record<typeof action, ConfirmDialogData> = {
      activate: {
        title: 'Activate project',
        message: `Activate “${project.name}”?`,
        confirmLabel: 'Activate',
      },
      archive: {
        title: 'Archive project',
        message: `Archive “${project.name}”?`,
        confirmLabel: 'Archive',
      },
      submit: {
        title: 'Submit for review',
        message: `Submit “${project.name}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve project',
        message: `Approve “${project.name}”?`,
        confirmLabel: 'Approve',
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
          action === 'activate'
            ? this.projectService.activate(project.id)
            : action === 'archive'
              ? this.projectService.archive(project.id)
              : action === 'submit'
                ? this.projectService.submit(project.id)
                : this.projectService.approve(project.id);
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

  canEdit(project: Project): boolean {
    return project.status === 'draft' || project.status === 'returned';
  }

  canActivate(project: Project): boolean {
    return project.status === 'approved' || project.status === 'draft';
  }

  canArchive(project: Project): boolean {
    return project.status === 'active' || project.status === 'completed';
  }

  canSubmit(project: Project): boolean {
    return project.status === 'draft' || project.status === 'returned';
  }

  canApprove(project: Project): boolean {
    return project.status === 'under_review';
  }
}
