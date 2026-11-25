import { Component, computed, inject, input, output } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';
import { AuthService } from '../../../core/services/auth.service';
import { NAVIGATION_CONFIG, NavGroup, NavItem } from '../../../core/config/navigation.config';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, MatIconModule, MatTooltipModule],
  templateUrl: './app-sidebar.html',
  styleUrl: './app-sidebar.scss',
})
export class AppSidebarComponent {
  private readonly auth = inject(AuthService);

  readonly collapsed = input(false);
  readonly navigate = output<void>();

  readonly groups = computed(() =>
    NAVIGATION_CONFIG.map((group) => ({
      ...group,
      items: group.items.filter((item) => this.auth.hasPermission(item.permission)),
    })).filter((group) => group.items.length > 0),
  );

  trackGroup = (_: number, group: NavGroup) => group.id;
  trackItem = (_: number, item: NavItem) => item.id;

  itemLink(item: NavItem): string {
    if (item.availability === 'coming_soon') {
      return `/app/coming-soon/${item.id}`;
    }
    return item.route ?? `/app/coming-soon/${item.id}`;
  }
}
