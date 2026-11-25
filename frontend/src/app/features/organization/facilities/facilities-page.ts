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
import { FacilityService, FacilityPayload } from '../../../core/services/facility.service';
import { OfficeService } from '../../../core/services/office.service';
import { Facility, Office } from '../../../core/models/organization.model';
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
  selector: 'app-facility-form-dialog',
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
    <h2 mat-dialog-title>{{ data.facility ? 'Edit facility' : 'Add facility' }}</h2>
    <mat-dialog-content>
      <form class="org-form" [formGroup]="form" id="facility-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Office</mat-label>
          <mat-select formControlName="office_id">
            @for (office of data.offices; track office.id) {
              <mat-option [value]="office.id">{{ office.name }}</mat-option>
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
          <mat-label>Type</mat-label>
          <input matInput formControlName="type" />
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
      <button mat-flat-button color="primary" type="submit" form="facility-form">Save</button>
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
export class FacilityFormDialogComponent {
  readonly data = inject<{ facility: Facility | null; offices: Office[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<FacilityFormDialogComponent, FacilityPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    office_id: [this.data.facility?.office_id ?? null as number | null, Validators.required],
    name: [this.data.facility?.name ?? '', Validators.required],
    code: [this.data.facility?.code ?? '', Validators.required],
    type: [this.data.facility?.type ?? ''],
    address: [this.data.facility?.address ?? ''],
    status: [this.data.facility?.status ?? 'active', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      office_id: value.office_id,
      name: value.name,
      code: value.code,
      type: value.type || null,
      address: value.address || null,
      status: value.status,
    });
  }
}

@Component({
  selector: 'app-facilities-page',
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
  templateUrl: './facilities-page.html',
  styleUrl: './facilities-page.scss',
})
export class FacilitiesPageComponent {
  private readonly facilityService = inject(FacilityService);
  private readonly officeService = inject(OfficeService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Facility[]>([]);
  readonly offices = signal<Office[]>([]);
  readonly displayedColumns = ['name', 'code', 'type', 'office', 'status', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
  });

  constructor() {
    this.officeService.list().subscribe({ next: (page) => this.offices.set(page.items) });
    this.reload();
  }

  officeName(id: number | null): string {
    if (!id) {
      return '—';
    }
    return this.offices().find((o) => o.id === id)?.name ?? `#${id}`;
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status } = this.filters.getRawValue();
    this.facilityService.list({ search: search || undefined, status: status || undefined }).subscribe({
      next: (page) => {
        this.items.set(page.items);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load facilities.');
        this.loading.set(false);
      },
    });
  }

  openForm(facility: Facility | null = null): void {
    this.dialog
      .open(FacilityFormDialogComponent, {
        width: '480px',
        data: { facility, offices: this.offices() },
      })
      .afterClosed()
      .subscribe((payload?: FacilityPayload) => {
        if (!payload) {
          return;
        }
        const request$ = facility
          ? this.facilityService.update(facility.id, payload)
          : this.facilityService.create(payload);
        request$.subscribe({
          next: () => {
            this.snackBar.open(facility ? 'Facility updated.' : 'Facility created.', 'Close', {
              duration: 3000,
            });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  confirmDelete(facility: Facility): void {
    const data: ConfirmDialogData = {
      title: 'Delete facility',
      message: `Delete “${facility.name}”? This cannot be undone.`,
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
        this.facilityService.delete(facility.id).subscribe({
          next: () => {
            this.snackBar.open('Facility deleted.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Delete failed.', 'Close', { duration: 4000 }),
        });
      });
  }
}
