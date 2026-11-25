import { Component, inject, signal } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { AuthService } from '../../../core/services/auth.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { OrganizationSetting } from '../../../core/models/organization.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';

@Component({
  selector: 'app-organization-settings',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatFormFieldModule,
    MatIconModule,
    MatInputModule,
    MatSnackBarModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
  ],
  templateUrl: './organization-settings.html',
  styleUrl: './organization-settings.scss',
})
export class OrganizationSettingsComponent {
  private readonly orgService = inject(OrganizationService);
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);

  readonly form = this.fb.group({
    settings: this.fb.array<FormGroup>([]),
  });

  get settings(): FormArray<FormGroup> {
    return this.form.controls.settings;
  }

  constructor() {
    this.reload();
  }

  createRow(setting?: OrganizationSetting): FormGroup {
    return this.fb.nonNullable.group({
      key: [setting?.key ?? '', Validators.required],
      value: [this.stringifyValue(setting?.value) ?? ''],
    });
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
    this.orgService.get(orgId).subscribe({
      next: (org) => {
        this.settings.clear();
        const items = org.settings ?? [];
        if (!items.length) {
          this.addRow();
        } else {
          for (const item of items) {
            this.settings.push(this.createRow(item));
          }
        }
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load organization settings.');
        this.loading.set(false);
      },
    });
  }

  addRow(): void {
    this.settings.push(this.createRow());
  }

  removeRow(index: number): void {
    this.settings.removeAt(index);
    if (!this.settings.length) {
      this.addRow();
    }
  }

  save(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const settings: OrganizationSetting[] = this.settings.getRawValue().map((row) => ({
      key: String(row['key'] ?? '').trim(),
      value: this.parseValue(String(row['value'] ?? '')),
    }));

    this.saving.set(true);
    this.orgService.updateSettings(settings).subscribe({
      next: (org) => {
        this.settings.clear();
        for (const item of org.settings ?? settings) {
          this.settings.push(this.createRow(item));
        }
        this.saving.set(false);
        this.snackBar.open('Settings saved.', 'Close', { duration: 3000 });
      },
      error: (err) => {
        this.saving.set(false);
        this.snackBar.open(err?.error?.message || 'Failed to save settings.', 'Close', {
          duration: 4000,
        });
      },
    });
  }

  private stringifyValue(value: unknown): string {
    if (value === null || value === undefined) {
      return '';
    }
    if (typeof value === 'string') {
      return value;
    }
    try {
      return JSON.stringify(value);
    } catch {
      return String(value);
    }
  }

  private parseValue(raw: string): unknown {
    const trimmed = raw.trim();
    if (!trimmed) {
      return null;
    }
    if (trimmed === 'true') {
      return true;
    }
    if (trimmed === 'false') {
      return false;
    }
    if (/^-?\d+(\.\d+)?$/.test(trimmed)) {
      return Number(trimmed);
    }
    if (
      (trimmed.startsWith('{') && trimmed.endsWith('}')) ||
      (trimmed.startsWith('[') && trimmed.endsWith(']'))
    ) {
      try {
        return JSON.parse(trimmed);
      } catch {
        return trimmed;
      }
    }
    return trimmed;
  }
}
