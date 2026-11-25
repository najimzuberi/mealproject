import { Component, inject, signal } from '@angular/core';
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
import { MatTooltipModule } from '@angular/material/tooltip';
import {
  ProgrammePayload,
  ProgrammeService,
} from '../../../core/services/programme.service';
import {
  Programme,
  PROGRAMME_STATUSES,
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
  selector: 'app-programme-form-dialog',
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
    <h2 mat-dialog-title>{{ data.programme ? 'Edit programme' : 'Create programme' }}</h2>
    <mat-dialog-content>
      <form class="programme-form programme-form--wide" [formGroup]="form" id="programme-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Name</mat-label>
          <input matInput formControlName="name" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Status</mat-label>
          <mat-select formControlName="status">
            @for (status of statuses; track status) {
              <mat-option [value]="status">{{ statusLabel(status) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="programme-form">Save</button>
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
export class ProgrammeFormDialogComponent {
  readonly data = inject<{ programme: Programme | null }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<ProgrammeFormDialogComponent, ProgrammePayload>);
  private readonly fb = inject(FormBuilder);

  readonly statuses = PROGRAMME_STATUSES;
  readonly statusLabel = statusLabel;

  readonly form = this.fb.nonNullable.group({
    code: [this.data.programme?.code ?? '', Validators.required],
    name: [this.data.programme?.name ?? '', Validators.required],
    description: [this.data.programme?.description ?? ''],
    status: [this.data.programme?.status ?? 'draft'],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      ...value,
      description: value.description || null,
    });
  }
}

@Component({
  selector: 'app-programmes-page',
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
  templateUrl: './programmes-page.html',
  styleUrl: './programmes-page.scss',
})
export class ProgrammesPageComponent {
  private readonly programmeService = inject(ProgrammeService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Programme[]>([]);
  readonly statuses = PROGRAMME_STATUSES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['name', 'code', 'status', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status } = this.filters.getRawValue();
    this.programmeService
      .list({
        search: search || undefined,
        status: status || undefined,
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
          this.items.set(rows);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load programmes.');
          this.loading.set(false);
        },
      });
  }

  openForm(programme: Programme | null = null): void {
    this.dialog
      .open(ProgrammeFormDialogComponent, {
        width: '640px',
        data: { programme },
      })
      .afterClosed()
      .subscribe((payload?: ProgrammePayload) => {
        if (!payload) {
          return;
        }
        const request$ = programme
          ? this.programmeService.update(programme.id, payload)
          : this.programmeService.create(payload);
        request$.subscribe({
          next: (saved) => {
            this.snackBar.open(
              programme ? 'Programme updated.' : 'Programme created.',
              'Close',
              { duration: 3000 },
            );
            if (!programme) {
              void this.router.navigate(['/app/programme/programmes', saved.id]);
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

  view(programme: Programme): void {
    void this.router.navigate(['/app/programme/programmes', programme.id]);
  }

  confirmDelete(programme: Programme): void {
    const data: ConfirmDialogData = {
      title: 'Delete programme',
      message: `Delete “${programme.name}”? This cannot be undone.`,
      confirmLabel: 'Delete',
    };
    this.dialog
      .open(ConfirmDialogComponent, { data })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.programmeService.delete(programme.id).subscribe({
          next: () => {
            this.snackBar.open('Programme deleted.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Delete failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  canEdit(programme: Programme): boolean {
    return programme.status === 'draft' || programme.status === 'active';
  }
}
