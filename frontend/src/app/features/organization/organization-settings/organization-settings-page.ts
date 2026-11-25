import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { AuthService } from '../../../core/services/auth.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { OrganizationSetting } from '../../../core/models/organization.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';

const SETTING_KEYS = ['timezone', 'language', 'currency', 'date_format'] as const;

type SettingKey = (typeof SETTING_KEYS)[number];

@Component({
  selector: 'app-organization-settings-page',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatSnackBarModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
  ],
  templateUrl: './organization-settings-page.html',
  styleUrl: './organization-settings-page.scss',
})
export class OrganizationSettingsPageComponent {
  private readonly orgService = inject(OrganizationService);
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);

  readonly dateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-M-Y'];

  readonly form = this.fb.nonNullable.group({
    timezone: ['UTC', Validators.required],
    language: ['en', Validators.required],
    currency: ['USD', Validators.required],
    date_format: ['Y-m-d', Validators.required],
  });

  constructor() {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.error.set(null);

    this.orgService.getSettings().subscribe({
      next: (settings) => {
        this.applySettings(settings);
        this.hydrateFromProfile();
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load organization settings.');
        this.loading.set(false);
      },
    });
  }

  save(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.saving.set(true);
    const value = this.form.getRawValue();
    const settings: OrganizationSetting[] = SETTING_KEYS.map((key) => ({
      key,
      value: value[key],
    }));

    this.orgService.updateSettings(settings).subscribe({
      next: (org) => {
        if (org.settings?.length) {
          this.applySettings(org.settings);
        }
        this.saving.set(false);
        this.snackBar.open('Organization settings saved.', 'Close', { duration: 3000 });
      },
      error: (err) => {
        this.saving.set(false);
        this.snackBar.open(err?.error?.message || 'Failed to save settings.', 'Close', {
          duration: 4000,
        });
      },
    });
  }

  private applySettings(settings: OrganizationSetting[]): void {
    const map = new Map(settings.map((s) => [s.key, s.value]));
    const patch: Partial<Record<SettingKey, string>> = {};

    const timezone = this.asString(map.get('timezone'));
    const language =
      this.asString(map.get('language')) || this.asString(map.get('locale'));
    const currency = this.asString(map.get('currency'));
    const dateFormat = this.asString(map.get('date_format'));

    if (timezone) {
      patch.timezone = timezone;
    }
    if (language) {
      patch.language = language;
    }
    if (currency) {
      patch.currency = currency;
    }
    if (dateFormat) {
      patch.date_format = dateFormat;
    }

    this.form.patchValue(patch);
  }

  private hydrateFromProfile(): void {
    const orgId = this.auth.organizationId();
    if (!orgId) {
      return;
    }

    this.orgService.get(orgId).subscribe({
      next: (profile) => {
        const current = this.form.getRawValue();
        this.form.patchValue({
          timezone: current.timezone || profile.timezone || 'UTC',
          language: current.language || profile.default_language || 'en',
          currency: current.currency || profile.default_currency || 'USD',
        });
      },
    });
  }

  private asString(value: unknown): string {
    if (value === null || value === undefined) {
      return '';
    }
    return String(value);
  }
}
