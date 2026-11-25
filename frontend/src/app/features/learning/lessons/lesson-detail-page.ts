import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTabsModule } from '@angular/material/tabs';
import { Lesson, statusLabel } from '../../../core/models/learning.model';
import { LessonService } from '../../../core/services/lesson.service';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';
import {
  ConfirmDialogComponent,
  ConfirmDialogData,
} from '../../../shared/components/confirm-dialog/confirm-dialog';

@Component({
  selector: 'app-lesson-detail-page',
  standalone: true,
  imports: [
    DatePipe,
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatFormFieldModule,
    MatInputModule,
    MatSnackBarModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './lesson-detail-page.html',
  styleUrl: './lesson-detail-page.scss',
})
export class LessonDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly lessonService = inject(LessonService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly lesson = signal<Lesson | null>(null);
  readonly statusLabel = statusLabel;

  readonly narrativeForm = this.fb.nonNullable.group({
    description: [''],
    context: [''],
    challenge: [''],
    what_worked: [''],
    what_did_not_work: [''],
    recommendation: [''],
    action_required: [''],
  });

  private lessonId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid lesson.');
      this.loading.set(false);
      return;
    }
    this.lessonId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.lessonService.get(this.lessonId).subscribe({
      next: (lesson) => {
        this.lesson.set(lesson);
        this.narrativeForm.patchValue({
          description: lesson.description ?? '',
          context: lesson.context ?? '',
          challenge: lesson.challenge ?? '',
          what_worked: lesson.what_worked ?? '',
          what_did_not_work: lesson.what_did_not_work ?? '',
          recommendation: lesson.recommendation ?? '',
          action_required: lesson.action_required ?? '',
        });
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load lesson.');
        this.loading.set(false);
      },
    });
  }

  goBack(): void {
    void this.router.navigate(['/app/learning/lessons']);
  }

  canEdit(lesson: Lesson): boolean {
    return (
      lesson.status === 'identified' ||
      lesson.status === 'documented' ||
      lesson.status === 'returned'
    );
  }

  canSubmit(lesson: Lesson): boolean {
    return (
      lesson.status === 'identified' ||
      lesson.status === 'documented' ||
      lesson.status === 'returned'
    );
  }

  canApprove(lesson: Lesson): boolean {
    return lesson.status === 'under_review';
  }

  canAssignAction(lesson: Lesson): boolean {
    return lesson.status === 'approved';
  }

  canApply(lesson: Lesson): boolean {
    return lesson.status === 'action_assigned';
  }

  saveNarrative(): void {
    const current = this.lesson();
    if (!current || !this.canEdit(current)) {
      return;
    }
    const value = this.narrativeForm.getRawValue();
    this.lessonService
      .update(current.id, {
        description: value.description || null,
        context: value.context || null,
        challenge: value.challenge || null,
        what_worked: value.what_worked || null,
        what_did_not_work: value.what_did_not_work || null,
        recommendation: value.recommendation || null,
        action_required: value.action_required || null,
        status: current.status === 'identified' ? 'documented' : current.status,
      })
      .subscribe({
        next: (saved) => {
          this.lesson.set(saved);
          this.snackBar.open('Lesson updated.', 'Close', { duration: 3000 });
        },
        error: (err) =>
          this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
      });
  }

  runWorkflow(action: 'submit' | 'approve' | 'assign' | 'apply'): void {
    const current = this.lesson();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      submit: {
        title: 'Submit lesson',
        message: `Submit “${current.title}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve lesson',
        message: `Approve “${current.title}”?`,
        confirmLabel: 'Approve',
      },
      assign: {
        title: 'Assign follow-up action',
        message: `Create an application action for “${current.title}”?`,
        confirmLabel: 'Assign',
      },
      apply: {
        title: 'Mark lesson applied',
        message: `Mark “${current.title}” as applied in practice?`,
        confirmLabel: 'Apply',
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
            ? this.lessonService.submit(current.id)
            : action === 'approve'
              ? this.lessonService.approve(current.id)
              : action === 'assign'
                ? this.lessonService.assignAction(current.id, {
                    description: current.action_required || `Apply lesson: ${current.title}`,
                  })
                : this.lessonService.apply(current.id);
        const successMessages: Record<typeof action, string> = {
          submit: 'Lesson submitted.',
          approve: 'Lesson approved.',
          assign: 'Follow-up action assigned.',
          apply: 'Lesson marked applied.',
        };
        request$.subscribe({
          next: (saved) => {
            this.lesson.set(saved);
            this.snackBar.open(successMessages[action], 'Close', { duration: 3000 });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Workflow action failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }
}
