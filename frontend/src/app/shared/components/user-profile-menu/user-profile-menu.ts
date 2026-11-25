import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { MatDividerModule } from '@angular/material/divider';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-user-profile-menu',
  standalone: true,
  imports: [RouterLink, MatButtonModule, MatIconModule, MatMenuModule, MatDividerModule],
  templateUrl: './user-profile-menu.html',
  styleUrl: './user-profile-menu.scss',
})
export class UserProfileMenuComponent {
  readonly auth = inject(AuthService);

  initials(): string {
    const name = this.auth.user()?.name ?? 'U';
    return name
      .split(' ')
      .map((p) => p[0])
      .join('')
      .slice(0, 2)
      .toUpperCase();
  }
}
