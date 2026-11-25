import { Component, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import { ProgrammeService } from '../../../core/services/programme.service';
import { ProjectService } from '../../../core/services/project.service';
import {
  Programme,
  Project,
  statusLabel,
} from '../../../core/models/programme-project.model';
import { ProgrammeFormDialogComponent } from './programmes-page';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-programme-detail-page',
  standalone: true,
  imports: [
    RouterLink,
    MatButtonModule,
    MatIconModule,
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
  templateUrl: './programme-detail-page.html',
  styleUrl: './programme-detail-page.scss',
})
export class ProgrammeDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly programmeService = inject(ProgrammeService);
  private readonly projectService = inject(ProjectService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly programme = signal<Programme | null>(null);
  readonly projects = signal<Project[]>([]);
  readonly statusLabel = statusLabel;
  readonly projectColumns = ['name', 'code', 'period', 'status', 'actions'];

  private programmeId = 0;

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid programme.');
      this.loading.set(false);
      return;
    }
    this.programmeId = id;
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.programmeService.get(this.programmeId).subscribe({
      next: (programme) => {
        this.programme.set(programme);
        this.loadProjects();
      },
      error: () => {
        this.error.set('Unable to load programme.');
        this.loading.set(false);
      },
    });
  }

  private loadProjects(): void {
    this.projectService.list({ programme_id: this.programmeId }).subscribe({
      next: (page) => {
        // Prefer API filter; fall back to client filter if API ignored programme_id.
        this.projects.set(page.items.filter((p) => p.programme_id === this.programmeId));
        this.loading.set(false);
      },
      error: () => {
        this.projects.set([]);
        this.loading.set(false);
      },
    });
  }

  openEdit(): void {
    const current = this.programme();
    if (!current) {
      return;
    }
    this.dialog
      .open(ProgrammeFormDialogComponent, {
        width: '640px',
        data: { programme: current },
      })
      .afterClosed()
      .subscribe((payload) => {
        if (!payload) {
          return;
        }
        this.programmeService.update(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Programme updated.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  goBack(): void {
    void this.router.navigate(['/app/programme/programmes']);
  }

  canEdit(programme: Programme): boolean {
    return programme.status === 'draft' || programme.status === 'active';
  }
}
