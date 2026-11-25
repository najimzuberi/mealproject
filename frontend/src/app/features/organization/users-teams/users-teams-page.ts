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
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { UserService, UserPayload } from '../../../core/services/user.service';
import { TeamService, TeamPayload } from '../../../core/services/team.service';
import { RoleService, OrgRole } from '../../../core/services/role.service';
import { DepartmentService } from '../../../core/services/department.service';
import { OfficeService } from '../../../core/services/office.service';
import { Department, Office, OrgUser, Team } from '../../../core/models/organization.model';
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
  selector: 'app-user-form-dialog',
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
    <h2 mat-dialog-title>{{ data.user ? 'Edit user' : 'Invite user' }}</h2>
    <mat-dialog-content>
      <form class="org-form" [formGroup]="form" id="user-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Name</mat-label>
          <input matInput formControlName="name" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Email</mat-label>
          <input matInput type="email" formControlName="email" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Phone</mat-label>
          <input matInput formControlName="phone" />
        </mat-form-field>
        @if (!data.user) {
          <mat-form-field appearance="outline">
            <mat-label>Temporary password</mat-label>
            <input matInput type="password" formControlName="password" />
          </mat-form-field>
        }
        <mat-form-field appearance="outline">
          <mat-label>Roles</mat-label>
          <mat-select formControlName="role_ids" multiple>
            @for (role of data.roles; track role.id) {
              <mat-option [value]="role.id">{{ role.display_name || role.name }}</mat-option>
            }
          </mat-select>
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
      <button mat-flat-button color="primary" type="submit" form="user-form">Save</button>
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
export class UserFormDialogComponent {
  readonly data = inject<{ user: OrgUser | null; roles: OrgRole[] }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(MatDialogRef<UserFormDialogComponent, UserPayload>);
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.nonNullable.group({
    name: [this.data.user?.name ?? '', Validators.required],
    email: [this.data.user?.email ?? '', [Validators.required, Validators.email]],
    phone: [this.data.user?.phone ?? ''],
    password: [''],
    role_ids: [this.data.user?.roles?.map((r) => r.id) ?? ([] as number[])],
    status: [this.data.user?.status ?? 'active', Validators.required],
  });

  constructor() {
    if (!this.data.user) {
      this.form.controls.password.addValidators([Validators.required, Validators.minLength(8)]);
    }
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    const payload: UserPayload = {
      name: value.name,
      email: value.email,
      phone: value.phone || null,
      status: value.status,
      role_ids: value.role_ids,
    };
    if (!this.data.user) {
      payload.password = value.password;
    }
    this.dialogRef.close(payload);
  }
}

@Component({
  selector: 'app-team-form-dialog',
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
    <h2 mat-dialog-title>{{ data.team ? 'Edit team' : 'Add team' }}</h2>
    <mat-dialog-content>
      <form class="org-form" [formGroup]="form" id="team-form" (ngSubmit)="submit()">
        <mat-form-field appearance="outline">
          <mat-label>Name</mat-label>
          <input matInput formControlName="name" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Code</mat-label>
          <input matInput formControlName="code" />
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Department</mat-label>
          <mat-select formControlName="department_id">
            <mat-option [value]="null">None</mat-option>
            @for (dept of data.departments; track dept.id) {
              <mat-option [value]="dept.id">{{ dept.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Office</mat-label>
          <mat-select formControlName="office_id">
            <mat-option [value]="null">None</mat-option>
            @for (office of data.offices; track office.id) {
              <mat-option [value]="office.id">{{ office.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Leader</mat-label>
          <mat-select formControlName="leader_user_id">
            <mat-option [value]="null">None</mat-option>
            @for (user of data.users; track user.id) {
              <mat-option [value]="user.id">{{ user.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Members</mat-label>
          <mat-select formControlName="member_ids" multiple>
            @for (user of data.users; track user.id) {
              <mat-option [value]="user.id">{{ user.name }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
        <mat-form-field appearance="outline">
          <mat-label>Description</mat-label>
          <textarea matInput rows="2" formControlName="description"></textarea>
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
      <button mat-flat-button color="primary" type="submit" form="team-form">Save</button>
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
export class TeamFormDialogComponent {
  readonly data = inject<{
    team: Team | null;
    users: OrgUser[];
    departments: Department[];
    offices: Office[];
  }>(MAT_DIALOG_DATA);
  private readonly dialogRef = inject(
    MatDialogRef<TeamFormDialogComponent, TeamPayload & { member_ids: number[] }>,
  );
  private readonly fb = inject(FormBuilder);

  readonly form = this.fb.group({
    name: [this.data.team?.name ?? '', Validators.required],
    code: [this.data.team?.code ?? ''],
    department_id: [this.data.team?.department_id ?? null as number | null],
    office_id: [this.data.team?.office_id ?? null as number | null],
    leader_user_id: [this.data.team?.leader_user_id ?? null as number | null],
    member_ids: [this.data.team?.members?.map((m) => m.id) ?? ([] as number[])],
    description: [this.data.team?.description ?? ''],
    status: [this.data.team?.status ?? 'active', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const value = this.form.getRawValue();
    this.dialogRef.close({
      name: value.name!,
      code: value.code || null,
      department_id: value.department_id,
      office_id: value.office_id,
      leader_user_id: value.leader_user_id,
      description: value.description || null,
      status: value.status!,
      member_ids: value.member_ids ?? [],
    });
  }
}

@Component({
  selector: 'app-users-teams-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatTableModule,
    MatTabsModule,
    MatSnackBarModule,
    MatTooltipModule,
    MatDialogModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './users-teams-page.html',
  styleUrl: './users-teams-page.scss',
})
export class UsersTeamsPageComponent {
  private readonly userService = inject(UserService);
  private readonly teamService = inject(TeamService);
  private readonly roleService = inject(RoleService);
  private readonly departmentService = inject(DepartmentService);
  private readonly officeService = inject(OfficeService);
  private readonly dialog = inject(MatDialog);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly usersLoading = signal(true);
  readonly teamsLoading = signal(true);
  readonly usersError = signal<string | null>(null);
  readonly teamsError = signal<string | null>(null);
  readonly users = signal<OrgUser[]>([]);
  readonly teams = signal<Team[]>([]);
  readonly roles = signal<OrgRole[]>([]);
  readonly departments = signal<Department[]>([]);
  readonly offices = signal<Office[]>([]);

  readonly userColumns = ['name', 'email', 'roles', 'status', 'actions'];
  readonly teamColumns = ['name', 'code', 'members', 'status', 'actions'];

  readonly userFilters = this.fb.nonNullable.group({
    search: [''],
    status: [''],
  });

  constructor() {
    this.roleService.list().subscribe({ next: (page) => this.roles.set(page.items) });
    this.departmentService.list().subscribe({ next: (page) => this.departments.set(page.items) });
    this.officeService.list().subscribe({ next: (page) => this.offices.set(page.items) });
    this.reloadUsers();
    this.reloadTeams();
  }

  roleNames(user: OrgUser): string {
    return user.roles?.map((r) => r.display_name || r.name).join(', ') || '—';
  }

  reloadUsers(): void {
    this.usersLoading.set(true);
    this.usersError.set(null);
    const { search, status } = this.userFilters.getRawValue();
    this.userService.list({ search: search || undefined, status: status || undefined }).subscribe({
      next: (page) => {
        this.users.set(page.items);
        this.usersLoading.set(false);
      },
      error: () => {
        this.usersError.set('Unable to load users.');
        this.usersLoading.set(false);
      },
    });
  }

  reloadTeams(): void {
    this.teamsLoading.set(true);
    this.teamsError.set(null);
    this.teamService.list().subscribe({
      next: (page) => {
        this.teams.set(page.items);
        this.teamsLoading.set(false);
      },
      error: () => {
        this.teamsError.set('Unable to load teams. The teams API may not be available yet.');
        this.teamsLoading.set(false);
      },
    });
  }

  openUserForm(user: OrgUser | null = null): void {
    this.dialog
      .open(UserFormDialogComponent, {
        width: '480px',
        data: { user, roles: this.roles() },
      })
      .afterClosed()
      .subscribe((payload?: UserPayload) => {
        if (!payload) {
          return;
        }
        const request$ = user
          ? this.userService.update(user.id, payload)
          : this.userService.create(payload);
        request$.subscribe({
          next: () => {
            this.snackBar.open(user ? 'User updated.' : 'User created.', 'Close', { duration: 3000 });
            this.reloadUsers();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  toggleUserStatus(user: OrgUser): void {
    const request$ =
      user.status === 'active'
        ? this.userService.deactivate(user.id)
        : this.userService.activate(user.id);
    request$.subscribe({
      next: () => {
        this.snackBar.open(
          user.status === 'active' ? 'User deactivated.' : 'User activated.',
          'Close',
          { duration: 3000 },
        );
        this.reloadUsers();
      },
      error: (err) =>
        this.snackBar.open(err?.error?.message || 'Status change failed.', 'Close', {
          duration: 4000,
        }),
    });
  }

  confirmRemoveUser(user: OrgUser): void {
    const data: ConfirmDialogData = {
      title: 'Remove user',
      message: `Remove “${user.name}” from this organization?`,
      confirmLabel: 'Remove',
      danger: true,
    };
    this.dialog
      .open(ConfirmDialogComponent, { data })
      .afterClosed()
      .subscribe((confirmed) => {
        if (!confirmed) {
          return;
        }
        this.userService.delete(user.id).subscribe({
          next: () => {
            this.snackBar.open('User removed.', 'Close', { duration: 3000 });
            this.reloadUsers();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Remove failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  openTeamForm(team: Team | null = null): void {
    this.dialog
      .open(TeamFormDialogComponent, {
        width: '520px',
        data: {
          team,
          users: this.users(),
          departments: this.departments(),
          offices: this.offices(),
        },
      })
      .afterClosed()
      .subscribe((payload?: TeamPayload & { member_ids: number[] }) => {
        if (!payload) {
          return;
        }
        const { member_ids, ...teamPayload } = payload;
        const save$ = team
          ? this.teamService.update(team.id, teamPayload)
          : this.teamService.create(teamPayload);
        save$.subscribe({
          next: (saved) => {
            this.teamService.syncMembers(saved.id, member_ids).subscribe({
              next: () => {
                this.snackBar.open(team ? 'Team updated.' : 'Team created.', 'Close', {
                  duration: 3000,
                });
                this.reloadTeams();
              },
              error: () => {
                this.snackBar.open('Team saved, but members could not be synced.', 'Close', {
                  duration: 4000,
                });
                this.reloadTeams();
              },
            });
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Save failed.', 'Close', { duration: 4000 }),
        });
      });
  }

  confirmDeleteTeam(team: Team): void {
    const data: ConfirmDialogData = {
      title: 'Delete team',
      message: `Delete “${team.name}”?`,
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
        this.teamService.delete(team.id).subscribe({
          next: () => {
            this.snackBar.open('Team deleted.', 'Close', { duration: 3000 });
            this.reloadTeams();
          },
          error: (err) =>
            this.snackBar.open(err?.error?.message || 'Delete failed.', 'Close', { duration: 4000 }),
        });
      });
  }
}
