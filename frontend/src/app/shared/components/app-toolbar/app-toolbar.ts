import { Component, output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { BreadcrumbComponent } from '../breadcrumb/breadcrumb';
import { OrganizationSwitcherComponent } from '../organization-switcher/organization-switcher';
import { NotificationMenuComponent } from '../notification-menu/notification-menu';
import { UserProfileMenuComponent } from '../user-profile-menu/user-profile-menu';

@Component({
  selector: 'app-toolbar',
  standalone: true,
  imports: [
    FormsModule,
    MatToolbarModule,
    MatIconModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    BreadcrumbComponent,
    OrganizationSwitcherComponent,
    NotificationMenuComponent,
    UserProfileMenuComponent,
  ],
  templateUrl: './app-toolbar.html',
  styleUrl: './app-toolbar.scss',
})
export class AppToolbarComponent {
  readonly toggleSidebar = output<void>();
  search = '';
}
