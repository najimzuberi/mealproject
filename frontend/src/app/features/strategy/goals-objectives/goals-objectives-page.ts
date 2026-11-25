import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTooltipModule } from '@angular/material/tooltip';
import { catchError, of, switchMap } from 'rxjs';
import {
  StrategicPlanPayload,
  StrategicPlanService,
} from '../../../core/services/strategic-plan.service';
import {
  StrategicGoal,
  StrategicObjective,
  StrategicPlan,
} from '../../../core/models/strategy.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-goal-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
  ],
  template: `
    <h2 mat-dialog-title>{{ data.goal ? 'Edit goal' : 'Add goal' }}</h2>
    <mat-dialog-content>
      <form class="strategy-form" [formGroup]="form" id="goal-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Expected result</mat-label>
          <textarea matInput rows="2" formControlName="expected_result"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="goal-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .strategy-form {
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
export class GoalFormDialogComponent {
  readonly data = inject<{ goal: StrategicGoal | null }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<GoalFormDialogComponent, Partial<StrategicGoal>>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    title: [this.data.goal?.title ?? '', Validators.required],
    code: [this.data.goal?.code ?? ''],
    description: [this.data.goal?.description ?? ''],
    expected_result: [this.data.goal?.expected_result ?? ''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      ...value,
      code: value.code || null,
      description: value.description || null,
      expected_result: value.expected_result || null,
    });
  }
}

@Component({
  selector: 'app-objective-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
  ],
  template: `
    <h2 mat-dialog-title>{{ data.objective ? 'Edit objective' : 'Add objective' }}</h2>
    <mat-dialog-content>
      <form class="strategy-form" [formGroup]="form" id="objective-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Expected result</mat-label>
          <textarea matInput rows="2" formControlName="expected_result"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="objective-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .strategy-form {
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
export class ObjectiveFormDialogComponent {
  readonly data = inject<{ objective: StrategicObjective | null }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<ObjectiveFormDialogComponent, Partial<StrategicObjective>>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    title: [this.data.objective?.title ?? '', Validators.required],
    code: [this.data.objective?.code ?? ''],
    description: [this.data.objective?.description ?? ''],
    expected_result: [this.data.objective?.expected_result ?? ''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      ...value,
      code: value.code || null,
      description: value.description || null,
      expected_result: value.expected_result || null,
    });
  }
}

@Component({
  selector: 'app-goals-objectives-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatSelectModule,
    MatExpansionModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './goals-objectives-page.html',
  styleUrl: './goals-objectives-page.scss',
})
export class GoalsObjectivesPageComponent {
  private readonly planService = inject(StrategicPlanService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly plans = signal<StrategicPlan[]>([]);
  readonly selectedPlan = signal<StrategicPlan | null>(null);

  readonly filters = this.fb.nonNullable.group({
    planId: [0 as number],
  });

  constructor() {
    this.loadPlans();
  }

  loadPlans(): void {
    this.loading.set(true);
    this.error.set(null);
    this.planService.list({ per_page: 100 }).subscribe({
      next: (page) => {
        this.plans.set(page.items);
        const queryPlan = Number(this.route.snapshot.queryParamMap.get('plan') || 0);
        const preferred =
          page.items.find((p) => p.id === queryPlan) ??
          page.items.find((p) => p.status === 'draft' || p.status === 'returned' || p.status === 'active') ??
          page.items[0];
        if (preferred) {
          this.filters.patchValue({ planId: preferred.id });
          this.loadPlan(preferred.id);
        } else {
          this.loading.set(false);
        }
      },
      error: () => {
        this.error.set('Unable to load strategic plans.');
        this.loading.set(false);
      },
    });
  }

  onPlanChange(): void {
    const planId = this.filters.getRawValue().planId;
    if (planId) {
      this.loadPlan(planId);
    }
  }

  loadPlan(planId: number): void {
    this.loading.set(true);
    this.error.set(null);
    this.planService.get(planId).subscribe({
      next: (plan) => {
        this.selectedPlan.set(plan);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load plan goals.');
        this.loading.set(false);
      },
    });
  }

  reload(): void {
    const planId = this.filters.getRawValue().planId || this.selectedPlan()?.id;
    if (planId) {
      this.loadPlan(planId);
      return;
    }
    this.loadPlans();
  }

  canEdit(): boolean {
    const status = this.selectedPlan()?.status;
    return status === 'draft' || status === 'returned';
  }

  openGoalForm(goal: StrategicGoal | null = null): void {
    const plan = this.selectedPlan();
    if (!plan) {
      return;
    }
    this.dialog
      .open(GoalFormDialogComponent, { width: '480px', data: { goal } })
      .afterClosed()
      .subscribe((payload?: Partial<StrategicGoal>) => {
        if (!payload) {
          return;
        }
        if (goal?.id) {
          this.persistGoalUpdate(plan, goal.id, payload);
        } else {
          this.persistGoalCreate(plan, payload);
        }
      });
  }

  openObjectiveForm(goal: StrategicGoal, objective: StrategicObjective | null = null): void {
    const plan = this.selectedPlan();
    if (!plan || !goal.id) {
      return;
    }
    this.dialog
      .open(ObjectiveFormDialogComponent, { width: '480px', data: { objective } })
      .afterClosed()
      .subscribe((payload?: Partial<StrategicObjective>) => {
        if (!payload) {
          return;
        }
        const goals = this.cloneGoals(plan);
        const target = goals.find((g) => g.id === goal.id);
        if (!target) {
          return;
        }
        const objectives = [...(target.objectives ?? [])];
        if (objective?.id) {
          const idx = objectives.findIndex((o) => o.id === objective.id);
          if (idx >= 0) {
            objectives[idx] = { ...objectives[idx], ...payload };
          }
        } else {
          objectives.push({
            title: payload.title ?? '',
            code: payload.code ?? null,
            description: payload.description ?? null,
            expected_result: payload.expected_result ?? null,
            sort_order: objectives.length,
            kra: [],
          });
        }
        target.objectives = objectives;
        this.saveGoals(plan.id, goals, objective ? 'Objective updated.' : 'Objective added.');
      });
  }

  private persistGoalCreate(plan: StrategicPlan, payload: Partial<StrategicGoal>): void {
    this.planService
      .createGoal(plan.id, payload)
      .pipe(
        catchError(() => {
          const goals = this.cloneGoals(plan);
          goals.push({
            title: payload.title ?? '',
            code: payload.code ?? null,
            description: payload.description ?? null,
            expected_result: payload.expected_result ?? null,
            sort_order: goals.length,
            objectives: [],
          });
          return this.planService.update(plan.id, { goals: this.toGoalPayload(goals) }).pipe(
            switchMap(() => of(null)),
          );
        }),
      )
      .subscribe({
        next: () => {
          this.snackBar.open('Goal added.', 'Close', { duration: 3000 });
          this.reload();
        },
        error: (err) =>
          this.snackBar.open(err?.error?.message || 'Unable to add goal.', 'Close', {
            duration: 4000,
          }),
      });
  }

  private persistGoalUpdate(
    plan: StrategicPlan,
    goalId: number,
    payload: Partial<StrategicGoal>,
  ): void {
    this.planService
      .updateGoal(plan.id, goalId, payload)
      .pipe(
        catchError(() => {
          const goals = this.cloneGoals(plan).map((g) =>
            g.id === goalId ? { ...g, ...payload } : g,
          );
          return this.planService.update(plan.id, { goals: this.toGoalPayload(goals) }).pipe(
            switchMap(() => of(null)),
          );
        }),
      )
      .subscribe({
        next: () => {
          this.snackBar.open('Goal updated.', 'Close', { duration: 3000 });
          this.reload();
        },
        error: (err) =>
          this.snackBar.open(err?.error?.message || 'Unable to update goal.', 'Close', {
            duration: 4000,
          }),
      });
  }

  private saveGoals(planId: number, goals: StrategicGoal[], successMessage: string): void {
    const payload: StrategicPlanPayload = { goals: this.toGoalPayload(goals) };
    this.planService.update(planId, payload).subscribe({
      next: () => {
        this.snackBar.open(successMessage, 'Close', { duration: 3000 });
        this.reload();
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
    });
  }

  private cloneGoals(plan: StrategicPlan): StrategicGoal[] {
    return (plan.goals ?? []).map((goal) => ({
      ...goal,
      objectives: (goal.objectives ?? []).map((objective) => ({
        ...objective,
        kra: [...(objective.kra ?? objective.key_result_areas ?? [])],
      })),
    }));
  }

  private toGoalPayload(goals: StrategicGoal[]): StrategicGoal[] {
    return goals.map((goal, index) => ({
      title: goal.title,
      code: goal.code ?? null,
      description: goal.description ?? null,
      expected_result: goal.expected_result ?? null,
      sort_order: goal.sort_order ?? index,
      strategic_theme_id: goal.strategic_theme_id ?? null,
      department_ids: goal.department_ids,
      objectives: (goal.objectives ?? []).map((objective, oIndex) => ({
        title: objective.title,
        code: objective.code ?? null,
        description: objective.description ?? null,
        expected_result: objective.expected_result ?? null,
        sort_order: objective.sort_order ?? oIndex,
        department_ids: objective.department_ids,
        responsible_user_ids: objective.responsible_user_ids,
        kra: (objective.kra ?? objective.key_result_areas ?? []).map((kra, kIndex) => ({
          title: kra.title,
          code: kra.code ?? null,
          description: kra.description ?? null,
          sort_order: kra.sort_order ?? kIndex,
        })),
      })),
    }));
  }
}
