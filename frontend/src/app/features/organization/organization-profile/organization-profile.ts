import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { MatIconModule } from '@angular/material/icon';
import { AuthService } from '../../../core/services/auth.service';
import { OrganizationService } from '../../../core/services/organization.service';
import { OrganizationProfile } from '../../../core/models/organization.model';
import { PageHeaderComponent } from '../../../shared/components/page-header/page-header';
import { LoadingStateComponent } from '../../../shared/components/loading-state/loading-state';
import { ErrorStateComponent } from '../../../shared/components/error-state/error-state';
import { StatusChipComponent } from '../../../shared/components/status-chip/status-chip';

@Component({
  selector: 'app-organization-profile',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatSnackBarModule,
    MatIconModule,
    PageHeaderComponent,
    LoadingStateComponent,
    ErrorStateComponent,
    StatusChipComponent,
  ],
  templateUrl: './organization-profile.html',
  styleUrl: './organization-profile.scss',
})
export class OrganizationProfileComponent {
  private readonly orgService = inject(OrganizationService);
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly snackBar = inject(MatSnackBar);

  readonly loading = signal(true);
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);
  readonly profile = signal<OrganizationProfile | null>(null);

  readonly types = ['government', 'ngo', 'private', 'international', 'other'];

  readonly form = this.fb.nonNullable.group({
    name: ['', Validators.required],
    code: ['', Validators.required],
    registration_number: [''],
    type: ['ngo', Validators.required],
    description: [''],
    email: [''],
    phone: [''],
    website: [''],
    country: [''],
    headquarters_region: [''],
    address: [''],
    physical_address: [''],
    postal_address: [''],
    timezone: [''],
    default_language: [''],
    default_currency: [''],
  });

  constructor() {
    this.reload();
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
      next: (profile) => {
        this.profile.set(profile);
        this.form.patchValue({
          name: profile.name ?? '',
          code: profile.code ?? '',
          registration_number: profile.registration_number ?? '',
          type: profile.type || 'ngo',
          description: profile.description ?? '',
          email: profile.email ?? '',
          phone: profile.phone ?? '',
          website: profile.website ?? '',
          country: profile.country ?? '',
          headquarters_region: profile.headquarters_region ?? '',
          address: profile.address ?? '',
          physical_address: profile.physical_address ?? '',
          postal_address: profile.postal_address ?? '',
          timezone: profile.timezone ?? '',
          default_language: profile.default_language ?? '',
          default_currency: profile.default_currency ?? '',
        });
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Unable to load organization profile.');
        this.loading.set(false);
      },
    });
  }

  save(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const orgId = this.auth.organizationId();
    if (!orgId) {
      return;
    }

    this.saving.set(true);
    const value = this.form.getRawValue();
    this.orgService
      .update(orgId, {
        ...value,
        registration_number: value.registration_number || null,
        description: value.description || null,
        email: value.email || null,
        phone: value.phone || null,
        website: value.website || null,
        country: value.country || null,
        headquarters_region: value.headquarters_region || null,
        address: value.address || null,
        physical_address: value.physical_address || null,
        postal_address: value.postal_address || null,
        timezone: value.timezone || null,
        default_language: value.default_language || null,
        default_currency: value.default_currency || null,
      })
      .subscribe({
        next: (profile) => {
          this.profile.set(profile);
          this.saving.set(false);
          this.snackBar.open('Organization profile saved.', 'Close', { duration: 3000 });
        },
        error: (err) => {
          this.saving.set(false);
          this.snackBar.open(err?.error?.message || 'Failed to save profile.', 'Close', {
            duration: 4000,
          });
        },
      });
  }

  onLogoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    const orgId = this.auth.organizationId();
    if (!file || !orgId) {
      return;
    }

    this.orgService.uploadLogo(orgId, file).subscribe({
      next: (profile) => {
        this.profile.set(profile);
        this.snackBar.open('Logo uploaded.', 'Close', { duration: 3000 });
        input.value = '';
      },
      error: (err) => {
        this.snackBar.open(err?.error?.message || 'Logo upload failed.', 'Close', { duration: 4000 });
        input.value = '';
      },
    });
  }
}
