import { BreakpointObserver, Breakpoints } from '@angular/cdk/layout';
import { Component, computed, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { RouterOutlet } from '@angular/router';
import { MatSidenavModule } from '@angular/material/sidenav';
import { map } from 'rxjs';
import { AppSidebarComponent } from '../../shared/components/app-sidebar/app-sidebar';
import { AppToolbarComponent } from '../../shared/components/app-toolbar/app-toolbar';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [RouterOutlet, MatSidenavModule, AppSidebarComponent, AppToolbarComponent],
  templateUrl: './main-layout.html',
  styleUrl: './main-layout.scss',
})
export class MainLayoutComponent {
  private readonly breakpoints = inject(BreakpointObserver);

  readonly isHandset = toSignal(
    this.breakpoints.observe([Breakpoints.Handset, Breakpoints.TabletPortrait]).pipe(map((r) => r.matches)),
    { initialValue: false },
  );

  readonly collapsedDesktop = signal(false);
  readonly mobileOpen = signal(false);

  readonly sidenavMode = computed(() => (this.isHandset() ? 'over' : 'side'));
  readonly sidenavOpened = computed(() => (this.isHandset() ? this.mobileOpen() : true));
  readonly sidebarCollapsed = computed(() => (!this.isHandset() ? this.collapsedDesktop() : false));

  toggle(): void {
    if (this.isHandset()) {
      this.mobileOpen.update((v) => !v);
    } else {
      this.collapsedDesktop.update((v) => !v);
    }
  }

  closeMobile(): void {
    if (this.isHandset()) {
      this.mobileOpen.set(false);
    }
  }
}
