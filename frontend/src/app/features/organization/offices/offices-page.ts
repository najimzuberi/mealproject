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
import { OfficeService, OfficePayload } from '../../../core/services/office.service';
import { DepartmentService } from '../../../core/services/department.service';
import { Department, Office } from '../../../core/models/organization.model';
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
  selector: 'app-office-form-dialog',
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
    <h2 mat-dialog-title>{{ data.office ? 'Edit office' : 'Add office' }}</h2>
    <mat-dialog-content>
      <form class="org-form" [formGroup]="form" id="office-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Department</mat-label>
          <mat-select formControlName="department_id">
            @for (dept of data.departments; track dept.id) {
              <mat-option [value]="dept.id">{{ dept.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Name</mat-label>
          <input matInput formControlName="name" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Address</mat-label>
          <textarea matInput rows="2" formControlName="address"></textarea>
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
      <button mat-flat-button color="primary" type="submit" form="office-form">Save</button>
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
export class OfficeFormDialogComponent {
  readonly data = inject<{ office: Office | null; departments: Department[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<OfficeFormDialogComponent, OfficePayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    department_id: [this.data.office?.department_id ?? null as number | null, Validators.required],
    name: [this.data.office?.name ?? '', Validators.required],
    code: [this.data.office?.code ?? '', Validators.required],
    address: [this.data.office?.address ?? ''],
    status: [this.data.office?.status ?? 'active', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      department_id: value.department_id,
      name: value.name,
      code: value.code,
      address: value.address || null,
      status: value.status,
    });
  }
}

@Component({
  selector: 'app-offices-page',
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
  templateUrl: './offices-page.html',
  styleUrl: './offices-page.scss',
})
export class OfficesPageComponent {
  private readonly officeService = inject(OfficeService);
  private readonly departmentService = inject(DepartmentService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Office[]>([]);
  readonly departments = signal<Department[]>([]);
  readonly displayedColumns = ['name', 'code', 'department', 'status', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
  });

  constructor() {
    this.departmentService.list().subscribe({
      next: (page) => this.departments.set(page.items),
    });
    this.reload();
  }

  departmentName(id: number | null): string {
    if (!id) {
      return '—';
    }
    return this.departments().find((d) => d.id === id)?.name ?? `#${id}`;
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status } = this.filters.getRawValue();
    this.officeService.list({ search: search || undefined, status: status || undefined }).subscribe({
      next: (page) => {
        this.items.set(page.items);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load offices.');
        this.loading.set(false);
      },
    });
  }

  openForm(office: Office | null = null): void {
    this.dialog
      .open(OfficeFormDialogComponent, {
        width: '480px',
        data: { office, departments: this.departments() },
      })
      .afterClosed()
      .subscribe((payload?: OfficePayload) => {
        if (!payload) {
          return;
        }
        const request$ = office
          ? this.officeService.update(office.id, payload)
          : this.officeService.create(payload);
        request$.subscribe({
          next: () => {
            this.snackBar.open(office ? 'Office updated.' : 'Office created.', 'Close', {
              duration: 3000,
            });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  confirmDelete(office: Office): void {
    const data: ConfirmDialogData = {
      title: 'Delete office',
      message: `Delete “${office.name}”? This cannot be undone.`,
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
        this.officeService.delete(office.id).subscribe({
          next: () => {
            this.snackBar.open('Office deleted.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Delete failed.', 'Close', { duration: 4000 }),
        });
      });
  }
}
