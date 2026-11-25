import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
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
import {
  Evaluation,
  EvaluationFinding,
  EvaluationRecommendation,
  LearningAction,
  PRIORITIES,
  statusLabel,
} from '../../../core/models/learning.model';
import {
  EvaluationFindingPayload,
  EvaluationRecommendationPayload,
  EvaluationService,
  LearningActionPayload,
  RecommendationRespondPayload,
} from '../../../core/services/evaluation.service';
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
  selector: 'app-finding-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
  ],
  template: `
    <h2 mat-dialog-title>Add finding</h2>
    <mat-dialog-content>
      <form class="dlg-form" [formGroup]="form" id="finding-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Reference</mat-label>
          <input matInput formControlName="reference" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Supporting evidence</mat-label>
          <textarea matInput rows="2" formControlName="supporting_evidence"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Conclusion</mat-label>
          <textarea matInput rows="2" formControlName="conclusion"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Significance</mat-label>
          <input matInput formControlName="significance" />
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="finding-form">Add</button>
    </mat-dialog-actions>
  `,
  styles: `
    .dlg-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
    }
    mat-form-field {
      width: 100%;
    }
  `,
})
export class FindingFormDialogComponent {
  private readonly dialogRef = inject(MatDialogRef<FindingFormDialogComponent, EvaluationFindingPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    reference: ['', Validators.required],
    title: ['', Validators.required],
    description: [''],
    supporting_evidence: [''],
    conclusion: [''],
    significance: [''],
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
      description: value.description || null,
      supporting_evidence: value.supporting_evidence || null,
      conclusion: value.conclusion || null,
      significance: value.significance || null,
    });
  }
}

@Component({
  selector: 'app-recommendation-form-dialog',
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
    <h2 mat-dialog-title>Add recommendation</h2>
    <mat-dialog-content>
      <form class="dlg-form" [formGroup]="form" id="rec-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
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
          <mat-label>Linked finding</mat-label>
          <mat-select formControlName="evaluation_finding_id">
            <mat-option [value]="null">—</mat-option>
            @for (finding of data.findings; track finding.id) {
              <mat-option [value]="finding.id">{{ finding.reference }} — {{ finding.title }}</mat-option>
            }
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
      <button mat-flat-button color="primary" type="submit" form="rec-form">Add</button>
    </mat-dialog-actions>
  `,
  styles: `
    .dlg-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
    }
    mat-form-field {
      width: 100%;
    }
  `,
})
export class RecommendationFormDialogComponent {
  readonly data = inject<{ findings: EvaluationFinding[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<RecommendationFormDialogComponent, EvaluationRecommendationPayload>,
  );
  private readonly fb = inject(FormBuilder);
  readonly priorities = PRIORITIES;
  readonly statusLabel = statusLabel;

  readonly form = this.fb.nonNullable.group({
    title: ['', Validators.required],
    priority: ['medium' as string],
    evaluation_finding_id: [null as number | null],
    description: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      title: value.title,
      priority: value.priority,
      evaluation_finding_id: value.evaluation_finding_id,
      description: value.description || null,
    });
  }
}

@Component({
  selector: 'app-respond-form-dialog',
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
    <h2 mat-dialog-title>Management response</h2>
    <mat-dialog-content>
      <form class="dlg-form" [formGroup]="form" id="respond-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Response status</mat-label>
          <mat-select formControlName="management_response_status">
            <mat-option value="accepted">Accepted</mat-option>
            <mat-option value="partially_accepted">Partially accepted</mat-option>
            <mat-option value="rejected">Rejected</mat-option>
            <mat-option value="under_consideration">Under consideration</mat-option>
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Response</mat-label>
          <textarea matInput rows="4" formControlName="management_response"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Response date</mat-label>
          <input matInput type="date" formControlName="response_date" />
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="respond-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .dlg-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
    }
    mat-form-field {
      width: 100%;
    }
  `,
})
export class RespondFormDialogComponent {
  private readonly dialogRef = inject(
    MatDialogRef<RespondFormDialogComponent, RecommendationRespondPayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    management_response_status: ['accepted', Validators.required],
    management_response: ['', Validators.required],
    response_date: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      management_response_status: value.management_response_status,
      management_response: value.management_response,
      response_date: value.response_date || null,
    });
  }
}

@Component({
  selector: 'app-learning-action-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
  ],
  template: `
    <h2 mat-dialog-title>Add learning action</h2>
    <mat-dialog-content>
      <form class="dlg-form" [formGroup]="form" id="action-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Due on</mat-label>
          <input matInput type="date" formControlName="due_on" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Required evidence</mat-label>
          <textarea matInput rows="2" formControlName="required_evidence"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="action-form">Add</button>
    </mat-dialog-actions>
  `,
  styles: `
    .dlg-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
    }
    mat-form-field {
      width: 100%;
    }
  `,
})
export class LearningActionFormDialogComponent {
  private readonly dialogRef = inject(
    MatDialogRef<LearningActionFormDialogComponent, LearningActionPayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    description: ['', Validators.required],
    due_on: [''],
    required_evidence: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      description: value.description,
      due_on: value.due_on || null,
      required_evidence: value.required_evidence || null,
    });
  }
}

@Component({
  selector: 'app-evaluation-detail-page',
  standalone: true,
  imports: [
    DatePipe,
    ReactiveFormsModule,
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
  templateUrl: './evaluation-detail-page.html',
  styleUrl: './evaluation-detail-page.scss',
})
export class EvaluationDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly evaluationService = inject(EvaluationService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly evaluation = signal<Evaluation | null>(null);
  readonly statusLabel = statusLabel;
  readonly findingColumns = ['reference', 'title', 'significance', 'actions'];
  readonly recommendationColumns = ['title', 'priority', 'response', 'actions'];
  readonly actionColumns = ['description', 'due_on', 'status', 'actions'];

  readonly overviewForm = this.fb.nonNullable.group({
    purpose: [''],
    scope: [''],
    methodology: [''],
    data_collection_methods: [''],
    target_population: [''],
    geographic_coverage: [''],
    tools: [''],
    timeline_notes: [''],
  });

  private evaluationId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid evaluation.');
      this.loading.set(false);
      return;
    }
    this.evaluationId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.evaluationService.get(this.evaluationId).subscribe({
      next: (evaluation) => {
        this.evaluation.set(evaluation);
        this.overviewForm.patchValue({
          purpose: evaluation.purpose ?? '',
          scope: evaluation.scope ?? '',
          methodology: evaluation.methodology ?? '',
          data_collection_methods: evaluation.data_collection_methods ?? '',
          target_population: evaluation.target_population ?? '',
          geographic_coverage: evaluation.geographic_coverage ?? '',
          tools: evaluation.tools ?? '',
          timeline_notes: evaluation.timeline_notes ?? '',
        });
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load evaluation.');
        this.loading.set(false);
      },
    });
  }

  goBack(): void {
    void this.router.navigate(['/app/learning/evaluations']);
  }

  findings(): EvaluationFinding[] {
    return this.evaluation()?.findings ?? [];
  }

  recommendations(): EvaluationRecommendation[] {
    return this.evaluation()?.recommendations ?? [];
  }

  learningActions(): LearningAction[] {
    return this.evaluation()?.learning_actions ?? [];
  }

  canEdit(evaluation: Evaluation): boolean {
    return evaluation.status === 'draft' || evaluation.status === 'returned';
  }

  canSubmit(evaluation: Evaluation): boolean {
    return evaluation.status === 'draft' || evaluation.status === 'returned';
  }

  canReturn(evaluation: Evaluation): boolean {
    return evaluation.status === 'under_review';
  }

  canApprove(evaluation: Evaluation): boolean {
    return evaluation.status === 'under_review';
  }

  canComplete(evaluation: Evaluation): boolean {
    return evaluation.status === 'approved' || evaluation.status === 'in_progress';
  }

  saveOverview(): void {
    const current = this.evaluation();
    if (!current || !this.canEdit(current)) {
      return;
    }
    const value = this.overviewForm.getRawValue();
    this.evaluationService
      .update(current.id, {
        purpose: value.purpose || null,
        scope: value.scope || null,
        methodology: value.methodology || null,
        data_collection_methods: value.data_collection_methods || null,
        target_population: value.target_population || null,
        geographic_coverage: value.geographic_coverage || null,
        tools: value.tools || null,
        timeline_notes: value.timeline_notes || null,
      })
      .subscribe({
        next: (saved) => {
          this.evaluation.set(saved);
          this.snackBar.open('Evaluation updated.', 'Close', { duration: 3000 });
        },
        error: (err) =>
          this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
      });
  }

  runWorkflow(action: 'submit' | 'return' | 'approve' | 'complete'): void {
    const current = this.evaluation();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit evaluation',
        message: `Submit “${current.title}” for review?`,
        confirmLabel: 'Submit',
      },
      return: {
        title: 'Return evaluation',
        message: `Return “${current.title}” for revision?`,
        confirmLabel: 'Return',
      },
      approve: {
        title: 'Approve evaluation',
        message: `Approve “${current.title}”?`,
        confirmLabel: 'Approve',
      },
      complete: {
        title: 'Complete evaluation',
        message: `Mark “${current.title}” as completed?`,
        confirmLabel: 'Complete',
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
            ? this.evaluationService.submit(current.id)
            : action === 'return'
              ? this.evaluationService.returnEvaluation(current.id)
              : action === 'approve'
                ? this.evaluationService.approve(current.id)
                : this.evaluationService.complete(current.id);
        const successMessages: Record<typeof action, string> = {
          submit: 'Evaluation submitted.',
          return: 'Evaluation returned.',
          approve: 'Evaluation approved.',
          complete: 'Evaluation completed.',
        };
        request$.subscribe({
          next: (saved) => {
            this.evaluation.set(saved);
            this.snackBar.open(successMessages[action], 'Close', { duration: 3000 });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Workflow action failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  addFinding(): void {
    const current = this.evaluation();
    if (!current) {
      return;
    }
    this.dialog
      .open(FindingFormDialogComponent, { width: '480px' })
      .afterClosed()
      .subscribe((payload?: EvaluationFindingPayload) => {
        if (!payload) {
          return;
        }
        this.evaluationService.addFinding(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Finding added.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Add finding failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  addRecommendation(): void {
    const current = this.evaluation();
    if (!current) {
      return;
    }
    this.dialog
      .open(RecommendationFormDialogComponent, {
        width: '480px',
        data: { findings: this.findings() },
      })
      .afterClosed()
      .subscribe((payload?: EvaluationRecommendationPayload) => {
        if (!payload) {
          return;
        }
        this.evaluationService.addRecommendation(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Recommendation added.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Add recommendation failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  respond(row: EvaluationRecommendation): void {
    this.dialog
      .open(RespondFormDialogComponent, { width: '480px' })
      .afterClosed()
      .subscribe((payload?: RecommendationRespondPayload) => {
        if (!payload) {
          return;
        }
        this.evaluationService.respondToRecommendation(row.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Response saved.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Response failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  addAction(): void {
    const current = this.evaluation();
    if (!current) {
      return;
    }
    this.dialog
      .open(LearningActionFormDialogComponent, { width: '480px' })
      .afterClosed()
      .subscribe((payload?: LearningActionPayload) => {
        if (!payload) {
          return;
        }
        this.evaluationService
          .createAction({
            ...payload,
            actionable_type: 'evaluation',
            actionable_id: current.id,
          })
          .subscribe({
            next: () => {
              this.snackBar.open('Learning action added.', 'Close', { duration: 3000 });
              this.reload();
            },
            error: (err) =>
              this.snackBar.open(err?.error?.message || 'Add action failed.', 'Close', {
                duration: 4000,
              }),
          });
      });
  }

  completeAction(row: LearningAction): void {
    this.evaluationService.completeAction(row.id).subscribe({
      next: () => {
        this.snackBar.open('Action completed.', 'Close', { duration: 3000 });
        this.reload();
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Complete failed.', 'Close', { duration: 4000 }),
    });
  }
}
