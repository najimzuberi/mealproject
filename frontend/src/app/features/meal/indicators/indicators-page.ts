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
import { IndicatorPayload, IndicatorService } from '../../../core/services/indicator.service';
import {
  DIRECTIONS_OF_CHANGE,
  INDICATOR_STATUSES,
  INDICATOR_TYPES,
  Indicator,
  RESULT_LEVELS,
  statusLabel,
} from '../../../core/models/meal.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-indicator-form-dialog',
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
    <h2 mat-dialog-title>{{ data.indicator ? 'Edit indicator' : 'Create indicator' }}</h2>
    <mat-dialog-content>
      <form class="meal-form meal-form--wide" [formGroup]="form" id="indicator-form" (ngSubmit)="submit()">
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
          <textarea matInput rows="2" formControlName="description"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Definition</mat-label>
          <textarea matInput rows="2" formControlName="definition"></textarea>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Measurement unit</mat-label>
          <input matInput formControlName="measurement_unit" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Result level</mat-label>
          <mat-select formControlName="result_level">
            <mat-option value="">—</mat-option>
            @for (level of resultLevels; track level) {
              <mat-option [value]="level">{{ statusLabel(level) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Indicator type</mat-label>
          <mat-select formControlName="indicator_type">
            <mat-option value="">—</mat-option>
            @for (type of indicatorTypes; track type) {
              <mat-option [value]="type">{{ statusLabel(type) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Direction of change</mat-label>
          <mat-select formControlName="direction_of_change">
            <mat-option value="">—</mat-option>
            @for (dir of directions; track dir) {
              <mat-option [value]="dir">{{ statusLabel(dir) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Data source</mat-label>
          <input matInput formControlName="data_source" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Collection frequency</mat-label>
          <input matInput formControlName="collection_frequency" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Collection method</mat-label>
          <input matInput formControlName="collection_method" />
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Means of verification</mat-label>
          <textarea matInput rows="2" formControlName="means_of_verification"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="indicator-form">Save</button>
    </mat-dialog-actions>
  `,
  styles: `
    .meal-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
      padding-top: 0.25rem;
    }
    .meal-form--wide {
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
      .meal-form--wide {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class IndicatorFormDialogComponent {
  readonly data = inject<{ indicator: Indicator | null }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<IndicatorFormDialogComponent, IndicatorPayload>);
  private readonly fb = inject(FormBuilder);

  readonly resultLevels = RESULT_LEVELS;
  readonly indicatorTypes = INDICATOR_TYPES;
  readonly directions = DIRECTIONS_OF_CHANGE;
  readonly statusLabel = statusLabel;

  readonly form = this.fb.nonNullable.group({
    code: [this.data.indicator?.code ?? '', Validators.required],
    name: [this.data.indicator?.name ?? '', Validators.required],
    description: [this.data.indicator?.description ?? ''],
    definition: [this.data.indicator?.definition ?? ''],
    measurement_unit: [this.data.indicator?.measurement_unit ?? ''],
    result_level: [this.data.indicator?.result_level ?? ''],
    indicator_type: [this.data.indicator?.indicator_type ?? ''],
    direction_of_change: [this.data.indicator?.direction_of_change ?? ''],
    data_source: [this.data.indicator?.data_source ?? ''],
    collection_method: [this.data.indicator?.collection_method ?? ''],
    collection_frequency: [this.data.indicator?.collection_frequency ?? ''],
    means_of_verification: [this.data.indicator?.means_of_verification ?? ''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      code: value.code,
      name: value.name,
      description: value.description || null,
      definition: value.definition || null,
      measurement_unit: value.measurement_unit || null,
      result_level: value.result_level || null,
      indicator_type: value.indicator_type || null,
      direction_of_change: value.direction_of_change || null,
      data_source: value.data_source || null,
      collection_method: value.collection_method || null,
      collection_frequency: value.collection_frequency || null,
      means_of_verification: value.means_of_verification || null,
    });
  }
}

@Component({
  selector: 'app-indicators-page',
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
  templateUrl: './indicators-page.html',
  styleUrl: './indicators-page.scss',
})
export class IndicatorsPageComponent {
  private readonly indicatorService = inject(IndicatorService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Indicator[]>([]);
  readonly statuses = INDICATOR_STATUSES;
  readonly resultLevels = RESULT_LEVELS;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = ['name', 'code', 'result_level', 'status', 'version', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    result_level: [''],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, result_level } = this.filters.getRawValue();
    this.indicatorService
      .list({
        search: search || undefined,
        status: status || undefined,
        result_level: result_level || undefined,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load indicators.');
          this.loading.set(false);
        },
      });
  }

  openForm(indicator: Indicator | null = null): void {
    this.dialog
      .open(IndicatorFormDialogComponent, {
        width: '720px',
        data: { indicator },
      })
      .afterClosed()
      .subscribe((payload?: IndicatorPayload) => {
        if (!payload) {
          return;
        }
        const request$ = indicator
          ? this.indicatorService.update(indicator.id, payload)
          : this.indicatorService.create(payload);
        request$.subscribe({
          next: (saved) => {
            this.snackBar.open(indicator ? 'Indicator updated.' : 'Indicator created.', 'Close', {
              duration: 3000,
            });
            if (!indicator) {
              void this.router.navigate(['/app/meal/indicators', saved.id]);
              return;
            }
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  view(row: Indicator): void {
    void this.router.navigate(['/app/meal/indicators', row.id]);
  }

  canEdit(row: Indicator): boolean {
    return row.status === 'draft' || row.status === 'returned';
  }
}
