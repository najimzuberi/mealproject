import { Component, inject, signal } from '@angular/core';
import { FormArray, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
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
  MonitoringPlanPayload,
  MonitoringPlanService,
} from '../../../core/services/monitoring-plan.service';
import { IndicatorService } from '../../../core/services/indicator.service';
import { ProjectService } from '../../../core/services/project.service';
import {
  Indicator,
  MONITORING_PLAN_STATUSES,
  MonitoringPlan,
  statusLabel,
} from '../../../core/models/meal.model';
import { Project } from '../../../core/models/programme-project.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-monitoring-plan-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatIconModule,
  ],
  template: `
    <h2 mat-dialog-title>{{ data.plan ? 'Edit monitoring plan' : 'Create monitoring plan' }}</h2>
    <mat-dialog-content>
      <form class="meal-form meal-form--wide" [formGroup]="form" id="mp-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Project</mat-label>
          <mat-select formControlName="project_id">
            @for (project of data.projects; track project.id) {
              <mat-option [value]="project.id">{{ project.code }} — {{ project.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description</mat-label>
          <textarea matInput rows="2" formControlName="description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Intended use</mat-label>
          <textarea matInput rows="2" formControlName="intended_use"></textarea>
        </mat-form-field>

        <div class="full-width items-block">
          <div class="items-block__header">
            <h3>Plan items</h3>
            <button mat-stroked-button type="button" (click)="addItem()">
              <mat-icon>add</mat-icon>
              Add indicator
            </button>
          </div>
          <div formArrayName="items" class="items-list">
            @for (group of items.controls; track $index; let i = $index) {
              <div class="item-row" [formGroupName]="i">
                <mat-form-field appearance="outline">
                  <mat-label>Indicator</mat-label>
                  <mat-select formControlName="indicator_id">
                    @for (ind of data.indicators; track ind.id) {
                      <mat-option [value]="ind.id">{{ ind.code }} — {{ ind.name }}</mat-option>
                    }
                  </mat-select>
                </mat-form-field>
                <mat-form-field appearance="outline">
                  <mat-label>Baseline</mat-label>
                  <input matInput formControlName="baseline_value" />
                </mat-form-field>
                <mat-form-field appearance="outline">
                  <mat-label>Target</mat-label>
                  <input matInput formControlName="target_value" />
                </mat-form-field>
                <mat-form-field appearance="outline">
                  <mat-label>Frequency</mat-label>
                  <input matInput formControlName="collection_frequency" />
                </mat-form-field>
                <button mat-icon-button type="button" (click)="removeItem(i)" aria-label="Remove item">
                  <mat-icon>close</mat-icon>
                </button>
              </div>
            }
          </div>
        </div>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="mp-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .meal-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
      padding-top: 0.25rem;
    }
    .meal-form--wide {
      min-width: min(100%, 44rem);
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem 1rem;
    }
    .full-width {
      grid-column: 1 / -1;
    }
    mat-form-field {
      width: 100%;
    }
    .items-block__header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
    }
    .items-block__header h3 {
      margin: 0;
      color: #142b45;
      font-size: 1rem;
    }
    .item-row {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr auto;
      gap: 0.5rem;
      align-items: start;
      margin-bottom: 0.5rem;
    }
    @media (max-width: 720px) {
      .meal-form--wide,
      .item-row {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class MonitoringPlanFormDialogComponent {
  readonly data = inject<{
    plan: MonitoringPlan | null;
    projects: Project[];
    indicators: Indicator[];
  }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<MonitoringPlanFormDialogComponent, MonitoringPlanPayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    project_id: [this.data.plan?.project_id ?? null as number | null, Validators.required],
    code: [this.data.plan?.code ?? '', Validators.required],
    title: [this.data.plan?.title ?? '', Validators.required],
    description: [this.data.plan?.description ?? ''],
    intended_use: [this.data.plan?.intended_use ?? ''],
    items: this.fb.array(
      (this.data.plan?.items ?? []).map((item) =>
        this.fb.nonNullable.group({
          indicator_id: [item.indicator_id, Validators.required],
          baseline_value: [item.baseline_value ?? ''],
          target_value: [item.target_value ?? ''],
          collection_frequency: [item.collection_frequency ?? ''],
        }),
      ),
    ),
  });

  constructor() {
    if (this.data.plan) {
      this.form.controls.project_id.disable();
    }
  }

  get items(): FormArray {
    return this.form.controls.items;
  }

  addItem(): void {
    this.items.push(
      this.fb.nonNullable.group({
        indicator_id: [null as number | null, Validators.required],
        baseline_value: [''],
        target_value: [''],
        collection_frequency: [''],
      }),
    );
  }

  removeItem(index: number): void {
    this.items.removeAt(index);
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    const payload: MonitoringPlanPayload = {
      code: value.code,
      title: value.title,
      description: value.description || null,
      intended_use: value.intended_use || null,
      items: value.items.map((item) => ({
        indicator_id: Number(item.indicator_id),
        baseline_value: item.baseline_value || null,
        target_value: item.target_value || null,
        collection_frequency: item.collection_frequency || null,
      })),
    };
    if (!this.data.plan) {
      payload.project_id = Number(value.project_id);
    }
    this.dialogRef.close(payload);
  }
}

@Component({
  selector: 'app-monitoring-plans-page',
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
  templateUrl: './monitoring-plans-page.html',
  styleUrl: './monitoring-plans-page.scss',
})
export class MonitoringPlansPageComponent {
  private readonly planService = inject(MonitoringPlanService);
  private readonly projectService = inject(ProjectService);
  private readonly indicatorService = inject(IndicatorService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<MonitoringPlan[]>([]);
  readonly projects = signal<Project[]>([]);
  readonly indicators = signal<Indicator[]>([]);
  readonly statuses = MONITORING_PLAN_STATUSES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['title', 'code', 'project', 'status', 'version', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    project_id: [''],
  });

  constructor() {
    this.loadLookups();
    this.reload();
  }

  private loadLookups(): void {
    this.projectService.list({ per_page: 100 }).subscribe({
      next: (page) => this.projects.set(page.items),
    });
    this.indicatorService.list({ status: 'approved', per_page: 100 }).subscribe({
      next: (page) => this.indicators.set(page.items),
      error: () =>
        this.indicatorService.list({ per_page: 100 }).subscribe({
          next: (page) => this.indicators.set(page.items),
        }),
    });
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, project_id } = this.filters.getRawValue();
    this.planService
      .list({
        search: search || undefined,
        status: status || undefined,
        project_id: project_id ? Number(project_id) : undefined,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load monitoring plans.');
          this.loading.set(false);
        },
      });
  }

  projectLabel(plan: MonitoringPlan): string {
    if (plan.project?.name) {
      return plan.project.code ? `${plan.project.code} — ${plan.project.name}` : plan.project.name;
    }
    const match = this.projects().find((p) => p.id === plan.project_id);
    return match ? `${match.code} — ${match.name}` : `#${plan.project_id}`;
  }

  openForm(plan: MonitoringPlan | null = null): void {
    this.dialog
      .open(MonitoringPlanFormDialogComponent, {
        width: '800px',
        data: {
          plan,
          projects: this.projects(),
          indicators: this.indicators(),
        },
      })
      .afterClosed()
      .subscribe((payload?: MonitoringPlanPayload) => {
        if (!payload) {
          return;
        }
        const request$ = plan
          ? this.planService.update(plan.id, payload)
          : this.planService.create(payload);
        request$.subscribe({
          next: (saved) => {
            this.snackBar.open(plan ? 'Plan updated.' : 'Plan created.', 'Close', {
              duration: 3000,
            });
            if (!plan) {
              void this.router.navigate(['/app/meal/monitoring-plans', saved.id]);
              return;
            }
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  view(row: MonitoringPlan): void {
    void this.router.navigate(['/app/meal/monitoring-plans', row.id]);
  }

  canEdit(row: MonitoringPlan): boolean {
    return row.status === 'draft' || row.status === 'returned';
  }
}
