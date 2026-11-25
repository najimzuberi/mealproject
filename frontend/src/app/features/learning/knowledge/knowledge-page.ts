import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import {
  KNOWLEDGE_DOCUMENT_STATUSES,
  KnowledgeDocument,
  statusLabel,
} from '../../../core/models/learning.model';
import {
  KnowledgeDocumentPayload,
  KnowledgeService,
} from '../../../core/services/knowledge.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-knowledge-form-dialog',
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
    <h2 mat-dialog-title>Add knowledge document</h2>
    <mat-dialog-content>
      <form class="learn-form learn-form--wide" [formGroup]="form" id="knowledge-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Category</mat-label>
          <input matInput formControlName="category" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Document type</mat-label>
          <input matInput formControlName="document_type" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Access level</mat-label>
          <mat-select formControlName="access_level">
            <mat-option value="public">Public</mat-option>
            <mat-option value="internal">Internal</mat-option>
            <mat-option value="restricted">Restricted</mat-option>
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Tags (comma-separated)</mat-label>
          <input matInput formControlName="tags" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="knowledge-form">Create</button>
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
export class KnowledgeFormDialogComponent {
  private readonly dialogRef = inject(
    MatDialogRef<KnowledgeFormDialogComponent, KnowledgeDocumentPayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    code: ['', Validators.required],
    title: ['', Validators.required],
    category: [''],
    document_type: [''],
    access_level: ['internal'],
    tags: [''],
    description: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    const tags = value.tags
      .split(',')
      .map((t) => t.trim())
      .filter(Boolean);
    this.dialogRef.close({
      code: value.code,
      title: value.title,
      category: value.category || null,
      document_type: value.document_type || null,
      access_level: value.access_level,
      tags: tags.length ? tags : null,
      description: value.description || null,
    });
  }
}

@Component({
  selector: 'app-knowledge-page',
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
  templateUrl: './knowledge-page.html',
  styleUrl: './knowledge-page.scss',
})
export class KnowledgePageComponent {
  private readonly knowledgeService = inject(KnowledgeService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<KnowledgeDocument[]>([]);
  readonly statuses = KNOWLEDGE_DOCUMENT_STATUSES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = [
    'code',
    'title',
    'category',
    'status',
    'version',
    'updated',
    'actions',
  ];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    category: [''],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, category } = this.filters.getRawValue();
    this.knowledgeService
      .list({
        search: search || undefined,
        status: status || undefined,
        category: category || undefined,
        per_page: 50,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load knowledge documents.');
          this.loading.set(false);
        },
      });
  }

  openCreate(): void {
    this.dialog
      .open(KnowledgeFormDialogComponent, { width: '640px' })
      .afterClosed()
      .subscribe((payload?: KnowledgeDocumentPayload) => {
        if (!payload) {
          return;
        }
        this.knowledgeService.create(payload).subscribe({
          next: (saved) => {
            this.snackBar.open('Document created.', 'Close', { duration: 3000 });
            void this.router.navigate(['/app/learning/knowledge', saved.id]);
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

  view(row: KnowledgeDocument): void {
    void this.router.navigate(['/app/learning/knowledge', row.id]);
  }
}
