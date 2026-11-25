import { Component } from '@angular/core';
import { MatBadgeModule } from '@angular/material/badge';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';

@Component({
  selector: 'app-notification-menu',
  standalone: true,
  imports: [MatBadgeModule, MatButtonModule, MatIconModule, MatMenuModule],
  template: `
    <button
      mat-icon-button
      type="button"
      [matMenuTriggerFor]="menu"
      aria-label="Notifications"
      [matBadge]="unread"
      [matBadgeHidden]="unread === 0"
      matBadgeColor="accent"
      matBadgeSize="small"
    >
      <mat-icon>notifications</mat-icon>
    </button>
    <mat-menu #menu="matMenu" class="notification-menu">
      <div class="notification-menu__empty" (click)="$event.stopPropagation()">
        No new notifications. Approval and collection alerts will appear here in Phase 3.
      </div>
    </mat-menu>
  `,
  styles: `
    .notification-menu__empty {
      padding: 1rem 1.1rem;
      max-width: 18rem;
      color: var(--mm-body);
      font-size: 0.85rem;
      white-space: normal;
    }
  `,
})
export class NotificationMenuComponent {
  readonly unread = 0;
}
