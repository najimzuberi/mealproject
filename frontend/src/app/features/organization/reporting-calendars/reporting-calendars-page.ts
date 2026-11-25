import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatDialog, MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import {
  ReportingCalendarPayload,
  ReportingCalendarService,
  ReportingPeriodPayload,
} from '../../../core/services/reporting-calendar.service';
import { ReportingCalendar, ReportingPeriod } from '../../../core/models/organization.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

const FREQUENCIES = [
  { value: 'monthly', label: 'Monthly' },
  { value: 'quarterly', label: 'Quarterly' },
  { value: 'semi_annual', label: 'Semi-annual' },
  { value: 'annual', label: 'Annual' },
  { value: 'custom', label: 'Custom' },
] as const;

@Component({
  selector: 'app-calendar-form-dialog',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatCheckboxModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
  ],
  template: `
    <h2 mat-dialog-title>{{ data.calendar ? 'Edit calendar' : 'Add calendar' }}</h2>
    <mat-dialog-content>
      <form class="org-form" [formGroup]="form" id="calendar-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Name</mat-label>
          <input matInput formControlName="name" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Frequency</mat-label>
          <mat-select formControlName="frequency">
            @for (freq of frequencies; track freq.value) {
              <mat-option [value]="freq.value">{{ freq.label }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput rows="3" formControlName="description"></textarea>
        </mat-form-field>
        <mat-checkbox formControlName="allow_overlapping_periods">
          Allow overlapping periods
        </mat-checkbox>
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
      <button mat-flat-button color="primary" type="submit" form="calendar-form">Save</button>
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
export class CalendarFormDialogComponent {
  readonly data = inject<{ calendar: ReportingCalendar | null }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<CalendarFormDialogComponent, ReportingCalendarPayload>,
  );
  private readonly fb = inject(FormBuilder);

  readonly frequencies = FREQUENCIES;

  readonly form = this.fb.nonNullable.group({
    name: [this.data.calendar?.name ?? '', Validators.required],
    frequency: [this.data.calendar?.frequency ?? 'monthly', Validators.required],
    description: [this.data.calendar?.description ?? ''],
    allow_overlapping_periods: [this.data.calendar?.allow_overlapping_periods ?? false],
    status: [this.data.calendar?.status ?? 'active', Validators.required],
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
  selector: 'app-period-form-dialog',
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
    <h2 mat-dialog-title>Add period</h2>
    <mat-dialog-content>
      <form class="org-form" [formGroup]="form" id="period-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Name</mat-label>
          <input matInput formControlName="name" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Starts on</mat-label>
          <input matInput type="date" formControlName="starts_on" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Ends on</mat-label>
          <input matInput type="date" formControlName="ends_on" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Status</mat-label>
          <mat-select formControlName="status">
            <mat-option value="draft">Draft</mat-option>
            <mat-option value="open">Open</mat-option>
            <mat-option value="closed">Closed</mat-option>
          </mat-select>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="period-form">Save</button>
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
export class PeriodFormDialogComponent {
  private readonly dialogRef = inject(MatDialogRef<PeriodFormDialogComponent, ReportingPeriodPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    name: ['', Validators.required],
    starts_on: ['', Validators.required],
    ends_on: ['', Validators.required],
    status: ['draft', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.dialogRef.close(this.form.getRawValue());
  }
}

@Component({
  selector: 'app-reporting-calendars-page',
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
  templateUrl: './reporting-calendars-page.html',
  styleUrl: './reporting-calendars-page.scss',
})
export class ReportingCalendarsPageComponent {
  private readonly calendarService = inject(ReportingCalendarService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<ReportingCalendar[]>([]);
  readonly selected = signal<ReportingCalendar | null>(null);
  readonly periodsLoading = signal(false);
  readonly periodsError = signal<string | null>(null);
  readonly periods = signal<ReportingPeriod[]>([]);

  readonly displayedColumns = ['name', 'frequency', 'status', 'actions'];
  readonly periodColumns = ['name', 'starts_on', 'ends_on', 'status', 'actions'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
  });

  constructor() {
    this.reload();
  }

  frequencyLabel(value: string): string {
    return FREQUENCIES.find((f) => f.value === value)?.label ?? value;
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status } = this.filters.getRawValue();
    this.calendarService
      .list({
        search: search || undefined,
        status: status || undefined,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
          const current = this.selected();
          if (current) {
            const refreshed = page.items.find((c) => c.id === current.id) ?? null;
            this.selected.set(refreshed);
            if (refreshed) {
              this.loadPeriods(refreshed.id);
            } else {
              this.periods.set([]);
            }
          }
        },
        error: () => {
          this.error.set('Unable to load reporting calendars.');
          this.loading.set(false);
        },
      });
  }

  selectCalendar(calendar: ReportingCalendar): void {
    this.selected.set(calendar);
    this.loadPeriods(calendar.id);
  }

  loadPeriods(calendarId: number): void {
    this.periodsLoading.set(true);
    this.periodsError.set(null);
    this.calendarService.listPeriods(calendarId).subscribe({
      next: (periods) => {
        this.periods.set(periods);
        this.periodsLoading.set(false);
      },
      error: () => {
        this.periodsError.set('Unable to load periods.');
        this.periodsLoading.set(false);
      },
    });
  }

  openForm(calendar: ReportingCalendar | null = null): void {
    this.dialog
      .open(CalendarFormDialogComponent, {
        width: '480px',
        data: { calendar },
      })
      .afterClosed()
      .subscribe((payload?: ReportingCalendarPayload) => {
        if (!payload) {
          return;
        }
        const request$ = calendar
          ? this.calendarService.update(calendar.id, payload)
          : this.calendarService.create(payload);
        request$.subscribe({
          next: (saved) => {
            this.snackBar.open(calendar ? 'Calendar updated.' : 'Calendar created.', 'Close', {
              duration: 3000,
            });
            if (!calendar) {
              this.selected.set(saved);
            }
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  openPeriodForm(): void {
    const calendar = this.selected();
    if (!calendar) {
      return;
    }
    this.dialog
      .open(PeriodFormDialogComponent, { width: '440px' })
      .afterClosed()
      .subscribe((payload?: ReportingPeriodPayload) => {
        if (!payload) {
          return;
        }
        this.calendarService.createPeriod(calendar.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Period added.', 'Close', { duration: 3000 });
            this.loadPeriods(calendar.id);
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Failed to add period.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  openPeriod(period: ReportingPeriod): void {
    this.calendarService.openPeriod(period.id).subscribe({
      next: () => {
        this.snackBar.open('Period opened.', 'Close', { duration: 3000 });
        const calendar = this.selected();
        if (calendar) {
          this.loadPeriods(calendar.id);
        }
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Failed to open period.', 'Close', {
          duration: 4000,
        }),
    });
  }

  closePeriod(period: ReportingPeriod): void {
    this.calendarService.closePeriod(period.id).subscribe({
      next: () => {
        this.snackBar.open('Period closed.', 'Close', { duration: 3000 });
        const calendar = this.selected();
        if (calendar) {
          this.loadPeriods(calendar.id);
        }
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Failed to close period.', 'Close', {
          duration: 4000,
        }),
    });
  }
}
