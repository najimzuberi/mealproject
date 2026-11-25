import { DatePipe } from '@angular/common';
import { Component, ElementRef, inject, signal, viewChild } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTabsModule } from '@angular/material/tabs';
import { KnowledgeDocument, statusLabel } from '../../../core/models/learning.model';
import { KnowledgeService } from '../../../core/services/knowledge.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';
import {
  ConfirmDialogComponent,
  ConfirmDialogData,
} from '../../../shared/components/confirm-dialog/confirm-dialog';

@Component({
  selector: 'app-knowledge-detail-page',
  standalone: true,
  imports: [
    DatePipe,
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatSnackBarModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './knowledge-detail-page.html',
  styleUrl: './knowledge-detail-page.scss',
})
export class KnowledgeDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly knowledgeService = inject(KnowledgeService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly fileInput = viewChild<ElementRef<HTMLInputElement>>('fileInput');

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly document = signal<KnowledgeDocument | null>(null);
  readonly statusLabel = statusLabel;

  readonly metaForm = this.fb.nonNullable.group({
    title: [''],
    category: [''],
    document_type: [''],
    description: [''],
    access_level: ['internal'],
    tags: [''],
    keywords: [''],
    ai_summary: [''],
  });

  private documentId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid knowledge document.');
      this.loading.set(false);
      return;
    }
    this.documentId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.knowledgeService.get(this.documentId).subscribe({
      next: (doc) => {
        this.document.set(doc);
        this.metaForm.patchValue({
          title: doc.title ?? '',
          category: doc.category ?? '',
          document_type: doc.document_type ?? '',
          description: doc.description ?? '',
          access_level: doc.access_level ?? 'internal',
          tags: (doc.tags ?? []).join(', '),
          keywords: (doc.keywords ?? []).join(', '),
          ai_summary: doc.ai_summary ?? '',
        });
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load knowledge document.');
        this.loading.set(false);
      },
    });
  }

  goBack(): void {
    void this.router.navigate(['/app/learning/knowledge']);
  }

  canEdit(doc: KnowledgeDocument): boolean {
    return doc.status === 'draft' || doc.status === 'published';
  }

  canArchive(doc: KnowledgeDocument): boolean {
    return doc.status !== 'archived';
  }

  hasFile(doc: KnowledgeDocument): boolean {
    return Boolean(doc.path || doc.original_name);
  }

  saveMeta(): void {
    const current = this.document();
    if (!current || !this.canEdit(current)) {
      return;
    }
    const value = this.metaForm.getRawValue();
    const split = (raw: string): string[] | null => {
      const parts = raw
        .split(',')
        .map((t) => t.trim())
        .filter(Boolean);
      return parts.length ? parts : null;
    };
    this.knowledgeService
      .update(current.id, {
        title: value.title || current.title,
        category: value.category || null,
        document_type: value.document_type || null,
        description: value.description || null,
        access_level: value.access_level,
        tags: split(value.tags),
        keywords: split(value.keywords),
        ai_summary: value.ai_summary || null,
      })
      .subscribe({
        next: (saved) => {
          this.document.set(saved);
          this.snackBar.open('Document updated.', 'Close', { duration: 3000 });
        },
        error: (err) =>
          this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
      });
  }

  triggerUpload(): void {
    this.fileInput()?.nativeElement.click();
  }

  onFileSelected(event: Event): void {
    const current = this.document();
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!current || !file) {
      return;
    }
    this.knowledgeService.upload(current.id, file).subscribe({
      next: (saved) => {
        this.document.set(saved);
        this.snackBar.open('File uploaded.', 'Close', { duration: 3000 });
        input.value = '';
      },
      error: (err) => {
        this.snackBar.open(err?.error?.message || 'Upload failed.', 'Close', { duration: 4000 });
        input.value = '';
      },
    });
  }

  download(): void {
    const current = this.document();
    if (!current) {
      return;
    }
    this.knowledgeService.download(current.id).subscribe({
      next: (blob) => {
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = current.original_name || `${current.code}-v${current.version ?? 1}`;
        anchor.click();
        URL.revokeObjectURL(url);
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Download failed.', 'Close', { duration: 4000 }),
    });
  }

  archive(): void {
    const current = this.document();
    if (!current) {
      return;
    }
    const data: ConfirmDialogData = {
      title: 'Archive document',
      message: `Archive “${current.title}”?`,
      confirmLabel: 'Archive',
      danger: true,
    };
    this.dialog
      .open(ConfirmDialogComponent, { data })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.knowledgeService.archive(current.id).subscribe({
          next: (saved) => {
            this.document.set(saved);
            this.snackBar.open('Document archived.', 'Close', { duration: 3000 });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Archive failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  formatSize(size: number | null | undefined): string {
    if (size == null) {
      return '—';
    }
    if (size < 1024) {
      return `${size} B`;
    }
    if (size < 1024 * 1024) {
      return `${(size / 1024).toFixed(1)} KB`;
    }
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
  }
}
