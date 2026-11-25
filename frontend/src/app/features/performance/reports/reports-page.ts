import { DatePipe } from '@angular/common';
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
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import {
  REPORT_STATUSES,
  REPORT_TYPES,
  Report,
  statusLabel,
} from '../../../core/models/performance.model';
import { Programme } from '../../../core/models/programme-project.model';
import { ReportingPeriod } from '../../../core/models/organization.model';
import { ReportPayload, ReportService } from '../../../core/services/report.service';
import { ProgrammeService } from '../../../core/services/programme.service';
import { ProjectService } from '../../../core/services/project.service';
import { ReportingCalendarService } from '../../../core/services/reporting-calendar.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-report-generate-dialog',
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
    <h2 mat-dialog-title>Generate report</h2>
    <mat-dialog-content>
      <form class="perf-form perf-form--wide" [formGroup]="form" id="report-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Title</mat-label>
          <input matInput formControlName="title" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Report type</mat-label>
          <mat-select formControlName="report_type">
            @for (type of reportTypes; track type) {
              <mat-option [value]="type">{{ statusLabel(type) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Programme</mat-label>
          <mat-select formControlName="programme_id">
            <mat-option [value]="null">—</mat-option>
            @for (programme of data.programmes; track programme.id) {
              <mat-option [value]="programme.id">{{ programme.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Project</mat-label>
          <mat-select formControlName="project_id">
            <mat-option [value]="null">—</mat-option>
            @for (project of data.projects; track project.id) {
              <mat-option [value]="project.id">{{ project.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Reporting period</mat-label>
          <mat-select formControlName="reporting_period_id">
            <mat-option [value]="null">—</mat-option>
            @for (period of data.periods; track period.id) {
              <mat-option [value]="period.id">{{ period.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Executive summary</mat-label>
          <textarea matInput rows="3" formControlName="executive_summary"></textarea>
        </mat-form-field>
      </form>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button type="button" mat-dialog-close>Cancel</button>
      <button mat-flat-button color="primary" type="submit" form="report-form">Generate</button>
    </mat-dialog-actions>
  `,
  styles: `
    .perf-form {
      display: grid;
      gap: 0.35rem;
      min-width: min(100%, 28rem);
      padding-top: 0.25rem;
    }
    .perf-form--wide {
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
      .perf-form--wide {
        grid-template-columns: 1fr;
      }
    }
  `,
})
export class ReportGenerateDialogComponent {
  readonly data = inject<{
    programmes: Programme[];
    projects: Array<{ id: number; name: string }>;
    periods: ReportingPeriod[];
  }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<ReportGenerateDialogComponent, ReportPayload>);
  private readonly fb = inject(FormBuilder);

  readonly reportTypes = REPORT_TYPES;
  readonly statusLabel = statusLabel;

  readonly form = this.fb.nonNullable.group({
    title: ['', Validators.required],
    report_type: ['quarterly' as string, Validators.required],
    programme_id: [null as number | null],
    project_id: [null as number | null],
    reporting_period_id: [null as number | null],
    executive_summary: [''],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      title: value.title,
      report_type: value.report_type,
      programme_id: value.programme_id,
      project_id: value.project_id,
      reporting_period_id: value.reporting_period_id,
      executive_summary: value.executive_summary || null,
    });
  }
}

@Component({
  selector: 'app-reports-page',
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
  templateUrl: './reports-page.html',
  styleUrl: './reports-page.scss',
})
export class ReportsPageComponent {
  private readonly reportService = inject(ReportService);
  private readonly programmeService = inject(ProgrammeService);
  private readonly projectService = inject(ProjectService);
  private readonly calendarService = inject(ReportingCalendarService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly items = signal<Report[]>([]);
  readonly programmes = signal<Programme[]>([]);
  readonly projects = signal<Array<{ id: number; name: string }>>([]);
  readonly periods = signal<ReportingPeriod[]>([]);
  readonly statuses = REPORT_STATUSES;
  readonly reportTypes = REPORT_TYPES;
  readonly statusLabel = statusLabel;
  readonly displayedColumns = [
    'reference',
    'title',
    'type',
    'status',
    'period',
    'updated',
    'actions',
  ];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
    report_type: [''],
  });

  constructor() {
    this.loadLookups();
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    const { search, status, report_type } = this.filters.getRawValue();
    this.reportService
      .list({
        search: search || undefined,
        status: status || undefined,
        report_type: report_type || undefined,
        per_page: 50,
      })
      .subscribe({
        next: (page) => {
          this.items.set(page.items);
          this.loading.set(false);
        },
        error: () => {
          this.error.set('Unable to load reports.');
          this.loading.set(false);
        },
      });
  }

  openGenerate(): void {
    this.dialog
      .open(ReportGenerateDialogComponent, {
        width: '640px',
        data: {
          programmes: this.programmes(),
          projects: this.projects(),
          periods: this.periods(),
        },
      })
      .afterClosed()
      .subscribe((payload?: ReportPayload) => {
        if (!payload) {
          return;
        }
        this.reportService.create(payload).subscribe({
          next: (saved) => {
            this.snackBar.open('Report generated.', 'Close', { duration: 3000 });
            void this.router.navigate(['/app/performance/reports', saved.id]);
          },
          error: (err) =>
            this.snackBar.open(
              err?.error?.message || 'Generate failed (API may be unavailable).',
              'Close',
              { duration: 4000 },
            ),
        });
      });
  }

  view(row: Report): void {
    void this.router.navigate(['/app/performance/reports', row.id]);
  }

  private loadLookups(): void {
    forkJoin({
      programmes: this.programmeService.list({ per_page: 100 }).pipe(
        catchError(() => of({ items: [] as Programme[] })),
      ),
      projects: this.projectService.list({ per_page: 100 }).pipe(
        catchError(() => of({ items: [] as Array<{ id: number; name: string }> })),
      ),
      calendars: this.calendarService.list({ per_page: 20 }).pipe(
        catchError(() => of({ items: [] })),
      ),
    }).subscribe({
      next: ({ programmes, projects, calendars }) => {
        this.programmes.set(programmes.items);
        this.projects.set(projects.items);
        const periods: ReportingPeriod[] = [];
        for (const calendar of calendars.items) {
          if (calendar.periods?.length) {
            periods.push(...calendar.periods);
          }
        }
        if (periods.length === 0 && calendars.items[0]) {
          this.calendarService.listPeriods(calendars.items[0].id).subscribe({
            next: (rows) => this.periods.set(rows),
            error: () => this.periods.set([]),
          });
        } else {
          this.periods.set(periods);
        }
      },
    });
  }
}
