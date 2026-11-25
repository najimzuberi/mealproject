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
import { GeographyService, GeographicUnitPayload } from '../../../core/services/geography.service';
import { GeographicUnit } from '../../../core/models/organization.model';
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
  selector: 'app-geography-form-dialog',
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
    <h2 mat-dialog-title>{{ data.unit ? 'Edit geographic unit' : 'Add geographic unit' }}</h2>
    <mat-dialog-content>
      <form class="org-form" [formGroup]="form" id="geo-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Parent unit</mat-label>
          <mat-select formControlName="parent_id">
            <mat-option [value]="null">None (root)</mat-option>
            @for (unit of data.units; track unit.id) {
              @if (!data.unit || unit.id !== data.unit.id) {
                <mat-option [value]="unit.id">{{ unit.name }} (L{{ unit.level }})</mat-option>
              }
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
          <input matInput formControlName="type" placeholder="region, district, ward…" />
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
      <button mat-flat-button color="primary" type="submit" form="geo-form">Save</button>
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
export class GeographyFormDialogComponent {
  readonly data = inject<{ unit: GeographicUnit | null; units: GeographicUnit[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<GeographyFormDialogComponent, GeographicUnitPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.group({
    parent_id: [this.data.unit?.parent_id ?? null as number | null],
    name: [this.data.unit?.name ?? '', Validators.required],
    code: [this.data.unit?.code ?? '', Validators.required],
    type: [this.data.unit?.type ?? 'unit'],
    status: [this.data.unit?.status ?? 'active', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      parent_id: value.parent_id,
      name: value.name!,
      code: value.code!,
      type: value.type || 'unit',
      status: value.status!,
    });
  }
}

@Component({
  selector: 'app-geography-page',
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
  templateUrl: './geography-page.html',
  styleUrl: './geography-page.scss',
})
export class GeographyPageComponent {
  private readonly geographyService = inject(GeographyService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<GeographicUnit[]>([]);
  readonly displayedColumns = ['name', 'code', 'level', 'type', 'parent', 'status', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
  });

  constructor() {
    this.reload();
  }

  parentName(parentId: number | null): string {
    if (!parentId) {
      return '—';
    }
    return this.items().find((u) => u.id === parentId)?.name ?? `#${parentId}`;
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status } = this.filters.getRawValue();
    this.geographyService
      .listUnits({ search: search || undefined, status: status || undefined })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load geographic units.');
          this.loading.set(false);
        },
      });
  }

  openForm(unit: GeographicUnit | null = null): void {
    this.dialog
      .open(GeographyFormDialogComponent, {
        width: '480px',
        data: { unit, units: this.items() },
      })
      .afterClosed()
      .subscribe((payload?: GeographicUnitPayload) => {
        if (!payload) {
          return;
        }
        const request$ = unit
          ? this.geographyService.updateUnit(unit.id, payload)
          : this.geographyService.createUnit(payload);
        request$.subscribe({
          next: () => {
            this.snackBar.open(unit ? 'Unit updated.' : 'Unit created.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  confirmDelete(unit: GeographicUnit): void {
    const data: ConfirmDialogData = {
      title: 'Delete geographic unit',
      message: `Delete “${unit.name}”? Child units may be affected.`,
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
        this.geographyService.deleteUnit(unit.id).subscribe({
          next: () => {
            this.snackBar.open('Geographic unit deleted.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Delete failed.', 'Close', { duration: 4000 }),
        });
      });
  }
}
