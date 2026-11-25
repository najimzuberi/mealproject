import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import { DepartmentService, DepartmentPayload } from '../../../core/services/department.service';
import { Department } from '../../../core/models/organization.model';
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
  selector: 'app-department-form-dialog',
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
    <h2 mat-dialog-title>{{ data.department ? 'Edit department' : 'Add department' }}</h2>
    <mat-dialog-content>
      <form class="org-form" [formGroup]="form" id="department-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Name</mat-label>
          <input matInput formControlName="name" />
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
          <mat-label>Status</mat-label>
          <mat-select formControlName="status">
            <mat-option value="active">Active</mat-option>
            <mat-option value="inactive">Inactive</mat-option>
          </mat-select>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="department-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .org-form {
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
export class DepartmentFormDialogComponent {
  readonly data = inject<{ department: Department | null }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<DepartmentFormDialogComponent, DepartmentPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    name: [this.data.department?.name ?? '', Validators.required],
    code: [this.data.department?.code ?? '', Validators.required],
    description: [this.data.department?.description ?? ''],
    status: [this.data.department?.status ?? 'active', Validators.required],
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
  selector: 'app-departments-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
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
  templateUrl: './departments-page.html',
  styleUrl: './departments-page.scss',
})
export class DepartmentsPageComponent {
  private readonly departmentService = inject(DepartmentService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Department[]>([]);
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
    this.departmentService
      .list({
        search: search || undefined,
        status: status || undefined,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load departments.');
          this.loading.set(false);
        },
      });
  }

  openForm(department: Department | null = null): void {
    this.dialog
      .open(DepartmentFormDialogComponent, {
        width: '480px',
        data: { department },
      })
      .afterClosed()
      .subscribe((payload?: DepartmentPayload) => {
        if (!payload) {
          return;
        }
        const request$ = department
          ? this.departmentService.update(department.id, payload)
          : this.departmentService.create(payload);
        request$.subscribe({
          next: () => {
            this.snackBar.open(department ? 'Department updated.' : 'Department created.', 'Close', {
              duration: 3000,
            });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  confirmDelete(department: Department): void {
    const data: ConfirmDialogData = {
      title: 'Delete department',
      message: `Delete “${department.name}”? This cannot be undone.`,
      confirmLabel: 'Delete',
      danger: true,
    };
    this.dialog
      .open(ConfirmDialogComponent, { data })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.departmentService.delete(department.id).subscribe({
          next: () => {
            this.snackBar.open('Department deleted.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Delete failed.', 'Close', { duration: 4000 }),
        });
      });
  }
}
