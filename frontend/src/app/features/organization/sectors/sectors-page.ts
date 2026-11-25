import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatTableModule } from '@angular/material/table';
import { AuthService } from '../../../core/services/auth.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { SectorService } from '../../../core/services/sector.service';
import { Sector } from '../../../core/models/organization.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-sectors-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatCheckboxModule,
    MatFormFieldModule,
    MatIconModule,
    MatInputModule,
    MatSnackBarModule,
    MatTableModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    EmptyStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './sectors-page.html',
  styleUrl: './sectors-page.scss',
})
export class SectorsPageComponent {
  private readonly sectorService = inject(SectorService);
  private readonly orgService = inject(OrganizationService);
  private readonly auth = inject(AuthService);
  private readonly snackBar = inject(MatSnackBar);
  private readonly fb = inject(FormBuilder);

  readonly loading = signal(true);
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);
  readonly catalog = signal<Sector[]>([]);
  readonly selectedIds = signal<Set<number>>(new Set());
  readonly displayedColumns = ['select', 'name', 'code', 'status'];

  readonly filters = this.fb.nonNullable.group({
    search: [''],
  });

  readonly filtered = computed(() => {
    const q = this.filters.controls.search.value.trim().toLowerCase();
    const items = this.catalog();
    if (!q) {
      return items;
    }
    return items.filter(
      (s) => s.name.toLowerCase().includes(q) || s.code.toLowerCase().includes(q),
    );
  });

  constructor() {
    this.reload();
  }

  isSelected(id: number): boolean {
    return this.selectedIds().has(id);
  }

  toggle(id: number, checked: boolean): void {
    const next = new Set(this.selectedIds());
    if (checked) {
      next.add(id);
    } else {
      next.delete(id);
    }
    this.selectedIds.set(next);
  }

  reload(): void {
    const orgId = this.auth.organizationId();
    if (!orgId) {
      this.error.set('No active organization selected.');
      this.loading.set(false);
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.sectorService.list().subscribe({
      next: (page) => {
        this.catalog.set(page.items);
        this.orgService.get(orgId).subscribe({
          next: (org) => {
            this.selectedIds.set(new Set((org.sectors ?? []).map((s) => s.id)));
            this.loading.set(false);
          },
          error: () => {
            this.error.set('Unable to load organization sectors.');
            this.loading.set(false);
          },
        });
      },
      error: () => {
        this.error.set('Unable to load sector catalog.');
        this.loading.set(false);
      },
    });
  }

  save(): void {
    const orgId = this.auth.organizationId();
    if (!orgId) {
      return;
    }
    this.saving.set(true);
    this.orgService.syncSectors(orgId, Array.from(this.selectedIds())).subscribe({
      next: (org) => {
        this.selectedIds.set(new Set((org.sectors ?? []).map((s) => s.id)));
        this.saving.set(false);
        this.snackBar.open('Organization sectors updated.', 'Close', { duration: 3000 });
      },
      error: (err) => {
        this.saving.set(false);
        this.snackBar.open(err?.error?.message || 'Failed to sync sectors.', 'Close', {
          duration: 4000,
        });
      },
    });
  }
}
