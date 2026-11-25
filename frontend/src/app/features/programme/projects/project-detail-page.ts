import { Component, inject, signal } from '@angular/core';
import { FormArray, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { forkJoin } from 'rxjs';
import { ProjectService } from '../../../core/services/project.service';
import { ProgrammeService } from '../../../core/services/programme.service';
import { ResultsFrameworkService } from '../../../core/services/results-framework.service';
import { StrategicPlanService } from '../../../core/services/strategic-plan.service';
import { UserService } from '../../../core/services/user.service';
import { GeographyService } from '../../../core/services/geography.service';
import {
  Programme,
  Project,
  ProjectTeamMember,
  ResultsFramework,
  formatBudget,
  statusLabel,
} from '../../../core/models/programme-project.model';
import { StrategicPlan } from '../../../core/models/strategy.model';
import { GeographicUnit, OrgUser } from '../../../core/models/organization.model';
import { ProjectFormDialogComponent } from './projects-page';
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
  selector: 'app-project-detail-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    RouterLink,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatTableModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './project-detail-page.html',
  styleUrl: './project-detail-page.scss',
})
export class ProjectDetailPageComponent {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly projectService = inject(ProjectService);
  private readonly programmeService = inject(ProgrammeService);
  private readonly planService = inject(StrategicPlanService);
  private readonly rfService = inject(ResultsFrameworkService);
  private readonly userService = inject(UserService);
  private readonly geographyService = inject(GeographyService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly project = signal<Project | null>(null);
  readonly programmes = signal<Programme[]>([]);
  readonly plans = signal<StrategicPlan[]>([]);
  readonly users = signal<OrgUser[]>([]);
  readonly geoUnits = signal<GeographicUnit[]>([]);
  readonly frameworks = signal<ResultsFramework[]>([]);
  readonly statusLabel = statusLabel;
  readonly formatBudget = formatBudget;
  readonly riskColumns = ['description', 'likelihood', 'impact', 'mitigation', 'status'];
  readonly activityColumns = ['title', 'period', 'status'];

  readonly teamForm = this.fb.group({
    members: this.fb.array<ReturnType<ProjectDetailPageComponent['createMemberGroup']>>([]),
  });

  readonly geoForm = this.fb.group({
    geographic_unit_ids: [[] as number[]],
  });

  private projectId = 0;

  get members(): FormArray {
    return this.teamForm.get('members') as FormArray;
  }

  constructor() {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error.set('Invalid project.');
      this.loading.set(false);
      return;
    }
    this.projectId = id;
    this.loadLookups();
    this.reload();
  }

  private createMemberGroup(member?: ProjectTeamMember) {
    return this.fb.group({
      user_id: [member?.user_id ?? null as number | null, Validators.required],
      role_label: [member?.role_label ?? ''],
    });
  }

  private loadLookups(): void {
    forkJoin({
      programmes: this.programmeService.list({ per_page: 100 }),
      plans: this.planService.list({ per_page: 100 }),
      users: this.userService.list({ per_page: 100 }),
      geo: this.geographyService.listUnits({ per_page: 100 }),
    }).subscribe({
      next: ({ programmes, plans, users, geo }) => {
        this.programmes.set(programmes.items);
        this.plans.set(plans.items);
        this.users.set(users.items);
        this.geoUnits.set(geo.items);
      },
    });
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);
    this.projectService.get(this.projectId).subscribe({
      next: (project) => {
        this.project.set(project);
        this.syncTeamForm(project.team_members ?? []);
        this.geoForm.patchValue({
          geographic_unit_ids: project.geographic_unit_ids ?? [],
        });
        this.loadFrameworks(project.id);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load project.');
        this.loading.set(false);
      },
    });
  }

  private loadFrameworks(projectId: number): void {
    this.rfService.list({ project_id: projectId }).subscribe({
      next: (page) => {
        this.frameworks.set(page.items.filter((f) => f.project_id === projectId));
      },
      error: () => this.frameworks.set([]),
    });
  }

  private syncTeamForm(members: ProjectTeamMember[]): void {
    this.members.clear();
    for (const member of members) {
      this.members.push(this.createMemberGroup(member));
    }
    if (!members.length) {
      this.members.push(this.createMemberGroup());
    }
  }

  addTeamMember(): void {
    this.members.push(this.createMemberGroup());
  }

  removeTeamMember(index: number): void {
    this.members.removeAt(index);
  }

  saveTeam(): void {
    if (this.teamForm.invalid) {
      this.teamForm.markAllAsTouched();
      return;
    }
    const members = this.members.getRawValue().map((m) => ({
      user_id: Number(m['user_id']),
      role_label: (m['role_label'] as string) || null,
    }));
    this.projectService.syncTeam(this.projectId, members).subscribe({
      next: (project) => {
        this.project.set(project);
        this.snackBar.open('Team updated.', 'Close', { duration: 3000 });
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Team update failed.', 'Close', {
          duration: 4000,
        }),
    });
  }

  saveGeo(): void {
    const ids = this.geoForm.getRawValue().geographic_unit_ids ?? [];
    this.projectService.syncGeographicUnits(this.projectId, ids).subscribe({
      next: (project) => {
        this.project.set(project);
        this.snackBar.open('Geographic units updated.', 'Close', { duration: 3000 });
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Geo update failed.', 'Close', {
          duration: 4000,
        }),
    });
  }

  openEdit(): void {
    const current = this.project();
    if (!current) {
      return;
    }
    this.dialog
      .open(ProjectFormDialogComponent, {
        width: '640px',
        data: {
          project: current,
          programmes: this.programmes(),
          plans: this.plans(),
        },
      })
      .afterClosed()
      .subscribe((payload) => {
        if (!payload) {
          return;
        }
        this.projectService.update(current.id, payload).subscribe({
          next: () => {
            this.snackBar.open('Project updated.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  runWorkflow(action: 'activate' | 'archive' | 'submit' | 'approve' | 'return'): void {
    const current = this.project();
    if (!current) {
      return;
    }
    const labels: Record<typeof action, ConfirmDialogData> = {
      activate: {
        title: 'Activate project',
        message: `Activate “${current.name}”?`,
        confirmLabel: 'Activate',
      },
      archive: {
        title: 'Archive project',
        message: `Archive “${current.name}”?`,
        confirmLabel: 'Archive',
      },
      submit: {
        title: 'Submit for review',
        message: `Submit “${current.name}” for review?`,
        confirmLabel: 'Submit',
      },
      approve: {
        title: 'Approve project',
        message: `Approve “${current.name}”?`,
        confirmLabel: 'Approve',
      },
      return: {
        title: 'Return project',
        message: `Return “${current.name}” for revision?`,
        confirmLabel: 'Return',
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
          action === 'activate'
            ? this.projectService.activate(current.id)
            : action === 'archive'
              ? this.projectService.archive(current.id)
              : action === 'submit'
                ? this.projectService.submit(current.id)
                : action === 'approve'
                  ? this.projectService.approve(current.id)
                  : this.projectService.returnProject(current.id);
        request$.subscribe({
          next: () => {
            this.snackBar.open('Workflow action completed.', 'Close', { duration: 3000 });
            this.reload();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Action failed.', 'Close', {
              duration: 4000,
            }),
        });
      });
  }

  goBack(): void {
    void this.router.navigate(['/app/programme/projects']);
  }

  programmeName(project: Project): string {
    if (project.programme?.name) {
      return project.programme.name;
    }
    if (!project.programme_id) {
      return '—';
    }
    return this.programmes().find((p) => p.id === project.programme_id)?.name ?? `#${project.programme_id}`;
  }

  canEdit(project: Project): boolean {
    return project.status === 'draft' || project.status === 'returned';
  }

  canSubmit(project: Project): boolean {
    return project.status === 'draft' || project.status === 'returned';
  }

  canApprove(project: Project): boolean {
    return project.status === 'under_review';
  }

  canReturn(project: Project): boolean {
    return project.status === 'under_review';
  }

  canActivate(project: Project): boolean {
    return project.status === 'approved' || project.status === 'draft';
  }

  canArchive(project: Project): boolean {
    return project.status === 'active' || project.status === 'completed' || project.status === 'approved';
  }
}
