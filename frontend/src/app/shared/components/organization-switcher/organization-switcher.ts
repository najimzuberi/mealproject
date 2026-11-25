import { Component, inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { AuthService } from '../../../core/services/auth.service';
import { OrganizationSummary } from '../../../core/models/api.model';

@Component({
  selector: 'app-organization-switcher',
  standalone: true,
  imports: [MatButtonModule, MatIconModule, MatMenuModule, MatSnackBarModule],
  templateUrl: './organization-switcher.html',
  styleUrl: './organization-switcher.scss',
})
export class OrganizationSwitcherComponent {
  private readonly auth = inject(AuthService);
  private readonly snackBar = inject(MatSnackBar);

  readonly organizations = () => this.auth.user()?.organizations ?? [];
  readonly active = this.auth.activeOrganization;

  switchTo(org: OrganizationSummary): void {
    if (org.id === this.active()?.id) {
      return;
    }
    this.auth.switchOrganization(org).subscribe({
      next: () => {
        this.snackBar.open(`Switched to ${org.name}`, 'OK', { duration: 2500 });
        location.reload();
      },
      error: () => this.snackBar.open('Unable to switch organization.', 'Dismiss', { duration: 3500 }),
    });
  }
}
