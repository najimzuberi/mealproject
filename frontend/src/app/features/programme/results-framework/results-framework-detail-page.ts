import { NgTemplateOutlet } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTooltipModule } from '@angular/material/tooltip';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import {
  ResultPayload,
  ResultsFrameworkService,
} from '../../../core/services/results-framework.service';
import {
  RESULT_LEVELS,
  Result,
  ResultsFramework,
  Project,
  flattenResults,
  resultLevelLabel,
  statusLabel,
} from '../../../core/models/programme-project.model';
import { ResultsFrameworkFormDialogComponent } from './results-frameworks-page';
import { ProjectService } from '../../../core/services/project.service';
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
  selector: 'app-result-form-dialog',
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
    <h2 mat-dialog-title>{{ data.result ? 'Edit result' : 'Add result' }}</h2>
    <mat-dialog-content>
      <form class="programme-form" [formGroup]="form" id="result-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Level</mat-label>
          <mat-select formControlName="level">
            @for (level of levels; track level) {
              <mat-option [value]="level">{{ resultLevelLabel(level) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Parent result</mat-label>
          <mat-select formControlName="parent_id">
            <mat-option [value]="null">None (root)</mat-option>
            @for (parent of data.parents; track parent.id) {
              <mat-option [value]="parent.id">
                {{ '—'.repeat(parent.depth) }} {{ parent.code ? parent.code + ' · ' : '' }}{{ parent.title }}
              </mat-option>
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
        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="result-form">Save</button>
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
export class ResultFormDialogComponent {
  readonly data = inject<{
    result: Result | null;
    parents: Array<Result & { depth: number }>;
    defaultParentId?: number | null;
    defaultLevel?: string;
  }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<ResultFormDialogComponent, ResultPayload>);
  private readonly fb = inject(FormBuilder);

  readonly levels = RESULT_LEVELS;
  readonly resultLevelLabel = resultLevelLabel;

  readonly form = this.fb.group({
    level: [
      this.data.result?.level ?? this.data.defaultLevel ?? 'outcome',
      Validators.required,
    ],
    parent_id: [
      this.data.result?.parent_id ?? this.data.defaultParentId ?? null as number | null,
    ],
    code: [this.data.result?.code ?? ''],
    title: [this.data.result?.title ?? '', Validators.required],
    description: [this.data.result?.description ?? ''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      level: value.level!,
      parent_id: value.parent_id,
      code: value.code || null,
      title: value.title!,
      description: value.description || null,
    });
  }
}

@Component({
  selector: 'app-results-framework-detail-page',
  standalone: true,
  imports: [
    NgTemplateOutlet,
    RouterLink,
    MatButtonModule,
    MatIconModule,
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
  templateUrl: './results-framework-detail-page.html',
  styleUrl: './results-framework-detail-page.scss',
})
export class ResultsFrameworkDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly rfService = inject(ResultsFrameworkService);
  private readonly projectService = inject(ProjectService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly framework = signal<ResultsFramework | null>(null);
  readonly tree = signal<Result[]>([]);
  readonly projects = signal<Project[]>([]);
  readonly statusLabel = statusLabel;
  readonly resultLevelLabel = resultLevelLabel;

  private frameworkId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid results framework.');
      this.loading.set(false);
      return;
    }
    this.frameworkId = id;
    this.projectService.list({ per_page: 100 }).subscribe({
      next: (page) => this.projects.set(page.items),
    });
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    forkJoin({
      framework: this.rfService.get(this.frameworkId),
      hierarchy: this.rfService.hierarchy(this.frameworkId).pipe(catchError(() => of([] as Result[]))),
    }).subscribe({
      next: ({ framework, hierarchy }) => {
        this.framework.set(framework);
        const tree =
          hierarchy.length > 0
            ? hierarchy
            : this.nestFlatResults(framework.results ?? []);
        this.tree.set(tree);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load results framework.');
        this.loading.set(false);
      },
    });
  }

  /** Build a tree if API returns a flat list without children. */
  private nestFlatResults(results: Result[]): Result[] {
    if (!results.length) {
      return [];
    }
    if (results.some((r) => (r.children?.length ?? 0) > 0)) {
      return results.filter((r) => !r.parent_id);
    }
    const byParent = new Map<number | null, Result[]>();
    for (const result of results) {
      const key = result.parent_id ?? null;
      const list = byParent.get(key) ?? [];
      list.push({ ...result, children: [] });
      byParent.set(key, list);
    }
    const attach = (nodes: Result[]): Result[] =>
      nodes.map((node) => ({
        ...node,
        children: attach(byParent.get(node.id) ?? []),
      }));
    return attach(byParent.get(null) ?? []);
  }

  openEdit(): void {
    const current = this.framework();
    if (!current) {
      return;
    }
    this.dialog
      .open(ResultsFrameworkFormDialogComponent, {
        width: '520px',
        data: {
          framework: current,
          projects: this.projects(),
        },
      })
      .afterClosed()
      .subscribe((payload) => {
        if (!payload) {
          return;
        }
        this.rfService.update(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Framework updated.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  openAddResult(parent: Result | null = null): void {
    const parents = flattenResults(this.tree());
    const defaultLevel =
      parent?.level === 'impact'
        ? 'outcome'
        : parent?.level === 'outcome'
          ? 'output'
          : parent?.level === 'output'
            ? 'activity'
            : 'impact';
    this.dialog
      .open(ResultFormDialogComponent, {
        width: '520px',
        data: {
          result: null,
          parents,
          defaultParentId: parent?.id ?? null,
          defaultLevel,
        },
      })
      .afterClosed()
      .subscribe((payload?: ResultPayload) => {
        if (!payload) {
          return;
        }
        this.rfService.addResult(this.frameworkId, payload).subscribe({
          next: () => {
            this.snackBar.open('Result added.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Add result failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  openEditResult(result: Result): void {
    const parents = flattenResults(this.tree()).filter((r) => r.id !== result.id);
    this.dialog
      .open(ResultFormDialogComponent, {
        width: '520px',
        data: { result, parents },
      })
      .afterClosed()
      .subscribe((payload?: ResultPayload) => {
        if (!payload) {
          return;
        }
        this.rfService.updateResult(result.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Result updated.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Update failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  linkIndicator(result: Result): void {
    const raw = window.prompt('Enter indicator ID to link:');
    if (!raw) {
      return;
    }
    const indicatorId = Number(raw);
    if (!indicatorId) {
      this.snackBar.open('Invalid indicator ID.', 'Close', { duration: 3000 });
      return;
    }
    this.rfService.linkIndicator(result.id, indicatorId).subscribe({
      next: () => {
        this.snackBar.open('Indicator linked.', 'Close', { duration: 3000 });
        this.reload();
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Link failed.', 'Close', { duration: 4000 }),
    });
  }

  runWorkflow(action: 'submit' | 'approve' | 'activate' | 'return' | 'archive'): void {
    const current = this.framework();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit for review',
        message: `Submit “${current.title}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve framework',
        message: `Approve “${current.title}”?`,
        confirmLabel: 'Approve',
      },
      activate: {
        title: 'Activate framework',
        message: `Activate “${current.title}”?`,
        confirmLabel: 'Activate',
      },
      return: {
        title: 'Return framework',
        message: `Return “${current.title}” for revision?`,
        confirmLabel: 'Return',
      },
      archive: {
        title: 'Archive framework',
        message: `Archive “${current.title}”?`,
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
            ? this.rfService.submit(current.id)
            : action === 'approve'
              ? this.rfService.approve(current.id)
              : action === 'activate'
                ? this.rfService.activate(current.id)
                : action === 'return'
                  ? this.rfService.returnFramework(current.id)
                  : this.rfService.archive(current.id);
        request$.subscribe({
          next: () => {
            this.snackBar.open('Workflow action completed.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Action failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  goBack(): void {
    void this.router.navigate(['/app/programme/results-frameworks']);
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

  canReturn(framework: ResultsFramework): boolean {
    return framework.status === 'under_review';
  }

  canActivate(framework: ResultsFramework): boolean {
    return framework.status === 'approved';
  }

  canArchive(framework: ResultsFramework): boolean {
    return framework.status === 'active' || framework.status === 'approved';
  }
}
