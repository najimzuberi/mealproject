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
  AnnualWorkPlanPayload,
  AnnualWorkPlanService,
} from '../../../core/services/annual-work-plan.service';
import { StrategicPlanService } from '../../../core/services/strategic-plan.service';
import {
  AnnualWorkPlan,
  StrategicObjective,
  StrategicPlan,
  STRATEGY_STATUSES,
  statusLabel,
} from '../../../core/models/strategy.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';
import {
  ConfirmDialogComponent,
  ConfirmDialogData,
} from '../../../shared/components/confirm-dialog/confirm-dialog';

type ObjectiveOption = StrategicObjective & { goal_title?: string };

@Component({
  selector: 'app-awp-form-dialog',
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
    <h2 mat-dialog-title>{{ data.awp ? 'Edit annual work plan' : 'Create annual work plan' }}</h2>
    <mat-dialog-content>
      <form class="strategy-form strategy-form--wide" [formGroup]="form" id="awp-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Strategic plan</mat-label>
          <mat-select formControlName="strategic_plan_id" (selectionChange)="onPlanChange()">
            @for (plan of data.plans; track plan.id) {
              <mat-option [value]="plan.id">{{ plan.code }} — {{ plan.title }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Planning year</mat-label>
          <input matInput type="number" formControlName="planning_year" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>

        <div class="full-width priorities-block">
          <div class="priorities-block__header">
            <h3>Priorities (optional)</h3>
            <button mat-stroked-button type="button" (click)="addPriority()">
              <mat-icon>add</mat-icon>
              Add priority
            </button>
          </div>
          @if (!objectives().length && form.controls.strategic_plan_id.value) {
            <p class="hint">No objectives on this plan yet — priorities can still be added later.</p>
          }
          <div formArrayName="priorities" class="priorities-list">
            @for (group of priorities.controls; track $index; let i = $index) {
              <div class="priority-row" [formGroupName]="i">
                <mat-form-field appearance="outline">
                  <mat-label>Objective</mat-label>
                  <mat-select formControlName="strategic_objective_id">
                    @for (obj of objectives(); track obj.id) {
                      <mat-option [value]="obj.id">
                        {{ obj.code ? obj.code + ' · ' : '' }}{{ obj.title }}
                        @if (obj.goal_title) {
                          ({{ obj.goal_title }})
                        }
                      </mat-option>
                    }
                  </mat-select>
                </mat-form-field>
                <mat-form-field appearance="outline">
                  <mat-label>Priority title</mat-label>
                  <input matInput formControlName="title" />
                </mat-form-field>
                <mat-form-field appearance="outline" class="full-width">
                  <mat-label>Description</mat-label>
                  <textarea matInput rows="2" formControlName="description"></textarea>
                </mat-form-field>
                <button
                  mat-icon-button
                  type="button"
                  aria-label="Remove priority"
                  (click)="removePriority(i)"
                >
                  <mat-icon>delete</mat-icon>
                </button>
              </div>
            }
          </div>
        </div>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="awp-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .strategy-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
      padding-top: 0.25rem;
    }
    .strategy-form--wide {
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
    .priorities-block__header {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 0.5rem;
    }
    .priorities-block__header h3 {
      margin: 0;
      font-size: 1rem;
    }
    .hint {
      margin: 0 0 0.5rem;
      font-size: 0.85rem;
      color: var(--mm-body, #5f6368);
    }
    .priorities-list {
      display: grid;
      gap: 0.75rem;
    }
    .priority-row {
      display: grid;
      grid-template-columns: 1fr 1fr auto;
      gap: 0.5rem 0.75rem;
      align-items: start;
      padding: 0.75rem;
      border: 1px solid var(--mm-border, #e0e0e0);
      border-radius: 8px;
    }
    .priority-row .full-width {
      grid-column: 1 / -1;
    }
    @media (max-width: 720px) {
      .strategy-form--wide,
      .priority-row {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class AwpFormDialogComponent {
  readonly data = inject<{ awp: AnnualWorkPlan | null; plans: StrategicPlan[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<AwpFormDialogComponent, AnnualWorkPlanPayload>);
  private readonly fb = inject(FormBuilder);
  private readonly planService = inject(StrategicPlanService);

  readonly objectives = signal<ObjectiveOption[]>([]);

  readonly form = this.fb.nonNullable.group({
    strategic_plan_id: [
      this.data.awp?.strategic_plan_id ?? this.data.plans[0]?.id ?? 0,
      Validators.required,
    ],
    title: [this.data.awp?.title ?? '', Validators.required],
    code: [this.data.awp?.code ?? '', Validators.required],
    planning_year: [
      this.data.awp?.planning_year ?? new Date().getFullYear(),
      [Validators.required, Validators.min(2000), Validators.max(2100)],
    ],
    description: [this.data.awp?.description ?? ''],
    priorities: this.fb.array(
      (this.data.awp?.priorities ?? []).map((priority) => this.createPriorityGroup(priority)),
    ),
  });

  get priorities(): FormArray {
    return this.form.controls.priorities;
  }

  constructor() {
    this.loadObjectives(this.form.controls.strategic_plan_id.value);
  }

  onPlanChange(): void {
    this.loadObjectives(this.form.controls.strategic_plan_id.value);
  }

  addPriority(): void {
    this.priorities.push(this.createPriorityGroup());
  }

  removePriority(index: number): void {
    this.priorities.removeAt(index);
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    const priorities = value.priorities
      .filter((row) => row.title.trim() && row.strategic_objective_id)
      .map((row, index) => ({
        strategic_objective_id: Number(row.strategic_objective_id),
        title: row.title.trim(),
        description: row.description?.trim() || null,
        sort_order: index,
      }));

    this.dialogRef.close({
      strategic_plan_id: value.strategic_plan_id,
      title: value.title,
      code: value.code,
      planning_year: value.planning_year,
      description: value.description || null,
      ...(priorities.length ? { priorities } : {}),
    });
  }

  private createPriorityGroup(priority?: {
    strategic_objective_id?: number;
    title?: string;
    description?: string | null;
  }) {
    return this.fb.nonNullable.group({
      strategic_objective_id: [priority?.strategic_objective_id ?? (null as number | null)],
      title: [priority?.title ?? ''],
      description: [priority?.description ?? ''],
    });
  }

  private loadObjectives(planId: number): void {
    if (!planId) {
      this.objectives.set([]);
      return;
    }
    this.planService.get(planId).subscribe({
      next: (plan) => {
        const options: ObjectiveOption[] = [];
        for (const goal of plan.goals ?? []) {
          for (const objective of goal.objectives ?? []) {
            if (objective.id) {
              options.push({ ...objective, goal_title: goal.title });
            }
          }
        }
        this.objectives.set(options);
      },
      error: () => this.objectives.set([]),
    });
  }
}

@Component({
  selector: 'app-annual-work-plans-page',
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
  templateUrl: './annual-work-plans-page.html',
  styleUrl: './annual-work-plans-page.scss',
})
export class AnnualWorkPlansPageComponent {
  private readonly awpService = inject(AnnualWorkPlanService);
  private readonly planService = inject(StrategicPlanService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<AnnualWorkPlan[]>([]);
  readonly plans = signal<StrategicPlan[]>([]);
  readonly statuses = STRATEGY_STATUSES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['title', 'code', 'year', 'plan', 'status', 'priorities', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    strategic_plan_id: ['' as string],
  });

  constructor() {
    this.planService.list({ per_page: 100 }).subscribe({
      next: (page) => this.plans.set(page.items),
    });
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, strategic_plan_id } = this.filters.getRawValue();
    this.awpService
      .list({
        search: search || undefined,
        status: status || undefined,
        strategic_plan_id: strategic_plan_id ? Number(strategic_plan_id) : undefined,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load annual work plans.');
          this.loading.set(false);
        },
      });
  }

  planTitle(awp: AnnualWorkPlan): string {
    if (awp.strategic_plan?.code) {
      return awp.strategic_plan.code;
    }
    const linked = this.plans().find((p) => p.id === awp.strategic_plan_id);
    return linked ? `${linked.code}` : String(awp.strategic_plan_id);
  }

  openForm(awp: AnnualWorkPlan | null = null): void {
    const eligiblePlans = this.plans().filter(
      (p) => p.status === 'approved' || p.status === 'active',
    );
    if (!eligiblePlans.length && !awp) {
      this.snackBar.open('Create and approve a strategic plan before adding an AWP.', 'Close', {
        duration: 4000,
      });
      return;
    }
    this.dialog
      .open(AwpFormDialogComponent, {
        width: '720px',
        data: { awp, plans: eligiblePlans.length ? eligiblePlans : this.plans() },
      })
      .afterClosed()
      .subscribe((payload?: AnnualWorkPlanPayload) => {
        if (!payload) {
          return;
        }
        const request$ = awp
          ? this.awpService.update(awp.id, payload)
          : this.awpService.create(payload);
        request$.subscribe({
          next: (saved) => {
            this.snackBar.open(awp ? 'AWP updated.' : 'AWP created.', 'Close', { duration: 3000 });
            if (!awp) {
              void this.router.navigate(['/app/strategy/annual-work-plans', saved.id]);
              return;
            }
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  view(awp: AnnualWorkPlan): void {
    void this.router.navigate(['/app/strategy/annual-work-plans', awp.id]);
  }

  runWorkflow(
    awp: AnnualWorkPlan,
    action: 'submit' | 'approve' | 'activate' | 'archive' | 'return',
  ): void {
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit for review',
        message: `Submit “${awp.title}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve AWP',
        message: `Approve “${awp.title}”?`,
        confirmLabel: 'Approve',
      },
      activate: {
        title: 'Activate AWP',
        message: `Activate “${awp.title}”?`,
        confirmLabel: 'Activate',
      },
      archive: {
        title: 'Archive AWP',
        message: `Archive “${awp.title}”?`,
        confirmLabel: 'Archive',
      },
      return: {
        title: 'Return AWP',
        message: `Return “${awp.title}” for revision?`,
        confirmLabel: 'Return',
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
            ? this.awpService.submit(awp.id)
            : action === 'approve'
              ? this.awpService.approve(awp.id)
              : action === 'activate'
                ? this.awpService.activate(awp.id)
                : action === 'archive'
                  ? this.awpService.archive(awp.id)
                  : this.awpService.returnPlan(awp.id);
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

  canEdit(awp: AnnualWorkPlan): boolean {
    return awp.status === 'draft' || awp.status === 'returned';
  }

  canSubmit(awp: AnnualWorkPlan): boolean {
    return awp.status === 'draft' || awp.status === 'returned';
  }

  canApprove(awp: AnnualWorkPlan): boolean {
    return awp.status === 'under_review';
  }

  canActivate(awp: AnnualWorkPlan): boolean {
    return awp.status === 'approved';
  }

  canArchive(awp: AnnualWorkPlan): boolean {
    return awp.status === 'active' || awp.status === 'approved';
  }
}
