import { DatePipe, DecimalPipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import {
  Report,
  ReportIndicatorResult,
  statusLabel,
} from '../../../core/models/performance.model';
import { ReportService } from '../../../core/services/report.service';
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
  selector: 'app-report-detail-page',
  standalone: true,
  imports: [
    DatePipe,
    DecimalPipe,
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatTableModule,
    MatFormFieldModule,
    MatInputModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './report-detail-page.html',
  styleUrl: './report-detail-page.scss',
})
export class ReportDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly reportService = inject(ReportService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly report = signal<Report | null>(null);
  readonly statusLabel = statusLabel;
  readonly resultColumns = [
    'indicator',
    'target',
    'actual',
    'achievement',
    'variance',
    'status',
  ];

  readonly narrativeForm = this.fb.nonNullable.group({
    executive_summary: [''],
    performance_summary: [''],
    challenges: [''],
    risks: [''],
    recommendations: [''],
  });

  private reportId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid report.');
      this.loading.set(false);
      return;
    }
    this.reportId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.reportService.get(this.reportId).subscribe({
      next: (report) => {
        this.report.set(report);
        this.narrativeForm.patchValue({
          executive_summary: report.executive_summary ?? '',
          performance_summary: report.performance_summary ?? '',
          challenges: report.challenges ?? '',
          risks: report.risks ?? '',
          recommendations: report.recommendations ?? '',
        });
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load report.');
        this.loading.set(false);
      },
    });
  }

  goBack(): void {
    void this.router.navigate(['/app/performance/reports']);
  }

  indicatorResults(): ReportIndicatorResult[] {
    return this.report()?.indicator_results ?? [];
  }

  canEdit(report: Report): boolean {
    return report.status === 'draft' || report.status === 'returned';
  }

  canSubmit(report: Report): boolean {
    return report.status === 'draft' || report.status === 'returned';
  }

  canReturn(report: Report): boolean {
    return report.status === 'under_review';
  }

  canApprove(report: Report): boolean {
    return report.status === 'under_review';
  }

  canPublish(report: Report): boolean {
    return report.status === 'approved';
  }

  saveNarrative(): void {
    const current = this.report();
    if (!current || !this.canEdit(current)) {
      return;
    }
    const value = this.narrativeForm.getRawValue();
    this.reportService
      .update(current.id, {
        executive_summary: value.executive_summary || null,
        performance_summary: value.performance_summary || null,
        challenges: value.challenges || null,
        risks: value.risks || null,
        recommendations: value.recommendations || null,
      })
      .subscribe({
        next: (saved) => {
          this.report.set(saved);
          this.snackBar.open('Report updated.', 'Close', { duration: 3000 });
        },
        error: (err) =>
          this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', {
            duration: 4000,
          }),
      });
  }

  runWorkflow(action: 'submit' | 'return' | 'approve' | 'publish'): void {
    const current = this.report();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit report',
        message: `Submit “${current.title}” for review?`,
        confirmLabel: 'Submit',
      },
      return: {
        title: 'Return report',
        message: `Return “${current.title}” for revision?`,
        confirmLabel: 'Return',
      },
      approve: {
        title: 'Approve report',
        message: `Approve “${current.title}”?`,
        confirmLabel: 'Approve',
      },
      publish: {
        title: 'Publish report',
        message: `Publish “${current.title}”? Published reports become official.`,
        confirmLabel: 'Publish',
      },
    };
    this.dialog
      .open(ConfirmDialogComponent, { data: labels[action] })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        const request$ =
          action === 'submit'
            ? this.reportService.submit(current.id)
            : action === 'return'
              ? this.reportService.returnReport(current.id)
              : action === 'approve'
                ? this.reportService.approve(current.id)
                : this.reportService.publish(current.id);
        const successMessages: Record<typeof action, string> = {
          submit: 'Report submitted.',
          return: 'Report returned.',
          approve: 'Report approved.',
          publish: 'Report published.',
        };
        request$.subscribe({
          next: (saved) => {
            this.report.set(saved);
            this.snackBar.open(successMessages[action], 'Close', { duration: 3000 });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Workflow action failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  exportPdf(): void {
    const current = this.report();
    if (!current) {
      return;
    }
    this.reportService.exportPdf(current.id).subscribe({
      next: (blob) => this.downloadBlob(blob, `${current.reference_number || current.id}.pdf`),
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'PDF export failed.', 'Close', {
          duration: 4000,
        }),
    });
  }

  exportExcel(): void {
    const current = this.report();
    if (!current) {
      return;
    }
    this.reportService.exportExcel(current.id).subscribe({
      next: (blob) => this.downloadBlob(blob, `${current.reference_number || current.id}.xlsx`),
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Excel export failed.', 'Close', {
          duration: 4000,
        }),
    });
  }

  private downloadBlob(blob: Blob, filename: string): void {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(url);
  }
}
