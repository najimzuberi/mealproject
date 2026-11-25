import { Component, ElementRef, ViewChild, inject, signal } from '@angular/core';
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
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { forkJoin } from 'rxjs';
import {
  ReportingAssignmentPayload,
  SubmissionPayload,
  SubmissionService,
} from '../../../core/services/submission.service';
import { MonitoringPlanService } from '../../../core/services/monitoring-plan.service';
import {
  ASSIGNMENT_STATUSES,
  MonitoringPlan,
  ReportingAssignment,
  SUBMISSION_STATUSES,
  Submission,
  statusLabel,
} from '../../../core/models/meal.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-assignment-form-dialog',
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
    <h2 mat-dialog-title>Create reporting assignment</h2>
    <mat-dialog-content>
      <form class="meal-form" [formGroup]="form" id="assignment-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Monitoring plan</mat-label>
          <mat-select formControlName="monitoring_plan_id" (selectionChange)="onPlanChange()">
            @for (plan of data.plans; track plan.id) {
              <mat-option [value]="plan.id">{{ plan.code }} — {{ plan.title }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Plan item</mat-label>
          <mat-select formControlName="monitoring_plan_item_id">
            @for (item of selectedItems(); track item.id) {
              <mat-option [value]="item.id">
                #{{ item.id }}
                @if (item.indicator?.name) {
                  — {{ item.indicator?.code }} {{ item.indicator?.name }}
                }
              </mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Assigned to (user id)</mat-label>
          <input matInput type="number" formControlName="assigned_to" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Due on</mat-label>
          <input matInput type="date" formControlName="due_on" />
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="assignment-form">Save</button>
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
export class AssignmentFormDialogComponent {
  readonly data = inject<{ plans: MonitoringPlan[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<AssignmentFormDialogComponent, ReportingAssignmentPayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly selectedItems = signal<NonNullable<MonitoringPlan['items']>>([]);

  readonly form = this.fb.nonNullable.group({
    monitoring_plan_id: [null as number | null, Validators.required],
    monitoring_plan_item_id: [null as number | null, Validators.required],
    assigned_to: [null as number | null, Validators.required],
    due_on: [''],
  });

  onPlanChange(): void {
    const planId = Number(this.form.controls.monitoring_plan_id.value);
    const plan = this.data.plans.find((p) => p.id === planId);
    this.selectedItems.set(plan?.items ?? []);
    this.form.controls.monitoring_plan_item_id.reset();
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      monitoring_plan_id: Number(value.monitoring_plan_id),
      monitoring_plan_item_id: Number(value.monitoring_plan_item_id),
      assigned_to: Number(value.assigned_to),
      due_on: value.due_on || null,
    });
  }
}

@Component({
  selector: 'app-submission-create-dialog',
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
    <h2 mat-dialog-title>New submission</h2>
    <mat-dialog-content>
      <form class="meal-form" [formGroup]="form" id="submission-create-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Monitoring plan</mat-label>
          <mat-select formControlName="monitoring_plan_id" (selectionChange)="onPlanChange()">
            @for (plan of data.plans; track plan.id) {
              <mat-option [value]="plan.id">{{ plan.code }} — {{ plan.title }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Plan item</mat-label>
          <mat-select formControlName="monitoring_plan_item_id" (selectionChange)="onItemChange()">
            @for (item of selectedItems(); track item.id) {
              <mat-option [value]="item.id">
                #{{ item.id }}
                @if (item.indicator?.name) {
                  — {{ item.indicator?.code }} {{ item.indicator?.name }}
                }
              </mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Value</mat-label>
          <input matInput type="number" formControlName="value" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Notes</mat-label>
          <textarea matInput rows="2" formControlName="notes"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="submission-create-form">Create</button>
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
export class SubmissionCreateDialogComponent {
  readonly data = inject<{ plans: MonitoringPlan[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<SubmissionCreateDialogComponent, SubmissionPayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly selectedItems = signal<NonNullable<MonitoringPlan['items']>>([]);
  private selectedIndicatorId: number | null = null;

  readonly form = this.fb.nonNullable.group({
    monitoring_plan_id: [null as number | null, Validators.required],
    monitoring_plan_item_id: [null as number | null, Validators.required],
    value: [''],
    notes: [''],
  });

  onPlanChange(): void {
    const planId = Number(this.form.controls.monitoring_plan_id.value);
    const plan = this.data.plans.find((p) => p.id === planId);
    this.selectedItems.set(plan?.items ?? []);
    this.form.controls.monitoring_plan_item_id.reset();
    this.selectedIndicatorId = null;
  }

  onItemChange(): void {
    const itemId = Number(this.form.controls.monitoring_plan_item_id.value);
    const item = this.selectedItems().find((i) => i.id === itemId);
    this.selectedIndicatorId = item?.indicator_id ?? null;
  }

  submit(): void {
    if (this.form.invalid || !this.selectedIndicatorId) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      monitoring_plan_id: Number(value.monitoring_plan_id),
      monitoring_plan_item_id: Number(value.monitoring_plan_item_id),
      indicator_id: this.selectedIndicatorId,
      value: value.value === '' ? null : Number(value.value),
      notes: value.notes || null,
    });
  }
}

@Component({
  selector: 'app-assignments-page',
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
    MatTabsModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './assignments-page.html',
  styleUrl: './assignments-page.scss',
})
export class AssignmentsPageComponent {
  @ViewChild('importInput') importInput?: ElementRef<HTMLInputElement>;

  private readonly submissionService = inject(SubmissionService);
  private readonly planService = inject(MonitoringPlanService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly apiUnavailable = signal(false);
  readonly assignments = signal<ReportingAssignment[]>([]);
  readonly submissions = signal<Submission[]>([]);
  readonly plans = signal<MonitoringPlan[]>([]);
  readonly assignmentStatuses = ASSIGNMENT_STATUSES;
  readonly submissionStatuses = SUBMISSION_STATUSES;
  readonly statusLabel = statusLabel;
  readonly assignmentColumns = ['plan', 'indicator', 'assignee', 'due_on', 'status', 'actions'];
  readonly submissionColumns = ['indicator', 'plan', 'value', 'status', 'submitted_at', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    status: [''],
    search: [''],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { status } = this.filters.getRawValue();
    forkJoin({
      assignments: this.submissionService.listAssignments({
        status: status || undefined,
      }),
      submissions: this.submissionService.listSubmissions({
        status: status || undefined,
      }),
      plans: this.planService.list({ status: 'active', per_page: 100 }),
    }).subscribe({
      next: ({ assignments, submissions, plans }) => {
        this.assignments.set(assignments.items);
        this.submissions.set(submissions.items);
        this.plans.set(plans.items);
        this.apiUnavailable.set(
          assignments.pagination.total === 0 &&
            submissions.pagination.total === 0 &&
            assignments.items.length === 0,
        );
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load data collection workspace.');
        this.loading.set(false);
      },
    });
  }

  openAssignmentForm(): void {
    const open = (plans: MonitoringPlan[]) => {
      this.dialog
        .open(AssignmentFormDialogComponent, {
          width: '480px',
          data: { plans },
        })
        .afterClosed()
        .subscribe((payload?: ReportingAssignmentPayload) => {
          if (!payload) {
            return;
          }
          this.submissionService.createAssignment(payload).subscribe({
            next: () => {
              this.snackBar.open('Assignment created.', 'Close', { duration: 3000 });
              this.reload();
            },
            error: (err) =>
              this.snackBar.open(err?.error?.message || 'Save failed (API may be unavailable).', 'Close', {
                duration: 4000,
              }),
          });
        });
    };

    if (this.plans().length) {
      open(this.plans());
      return;
    }
    this.planService.list({ per_page: 100 }).subscribe({
      next: (page) => {
        this.plans.set(page.items);
        open(page.items);
      },
      error: () => open([]),
    });
  }

  openSubmissionForm(): void {
    this.dialog
      .open(SubmissionCreateDialogComponent, {
        width: '480px',
        data: { plans: this.plans() },
      })
      .afterClosed()
      .subscribe((payload?: SubmissionPayload) => {
        if (!payload) {
          return;
        }
        this.submissionService.createSubmission(payload).subscribe({
          next: (saved) => {
            this.snackBar.open('Submission created.', 'Close', { duration: 3000 });
            void this.router.navigate(['/app/meal/data-collection/submissions', saved.id]);
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed (API may be unavailable).', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  triggerImport(): void {
    this.importInput?.nativeElement.click();
  }

  onImportSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
      return;
    }
    this.submissionService.importFile(file).subscribe({
      next: () => {
        this.snackBar.open('Import started.', 'Close', { duration: 3000 });
        this.reload();
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Import failed (API may be unavailable).', 'Close', {
          duration: 4000,
        }),
    });
    input.value = '';
  }

  viewSubmission(row: Submission): void {
    void this.router.navigate(['/app/meal/data-collection/submissions', row.id]);
  }
}
