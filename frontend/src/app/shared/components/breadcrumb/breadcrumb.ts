import { Component, inject } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router, RouterLink } from '@angular/router';
import { filter, map, startWith } from 'rxjs';
import { toSignal } from '@angular/core/rxjs-interop';
import { MatIconModule } from '@angular/material/icon';

interface Crumb {
  label: string;
  url: string;
}

@Component({
  selector: 'app-breadcrumb',
  standalone: true,
  imports: [RouterLink, MatIconModule],
  template: `
    <nav class="crumbs" aria-label="Breadcrumb">
      <a routerLink="/app/dashboard">Home</a>
      @for (crumb of crumbs(); track crumb.url) {
        <mat-icon aria-hidden="true">chevron_right</mat-icon>
        <a [routerLink]="crumb.url">{{ crumb.label }}</a>
      }
    </nav>
  `,
  styles: `
    .crumbs {
      display: flex;
      align-items: center;
      gap: 0.15rem;
      flex-wrap: wrap;
      font-size: 0.85rem;
      color: var(--mm-body);
    }
    a {
      color: inherit;
      text-decoration: none;
    }
    a:hover {
      color: var(--mm-secondary);
    }
    mat-icon {
      font-size: 1rem;
      width: 1rem;
      height: 1rem;
      opacity: 0.6;
    }
  `,
})
export class BreadcrumbComponent {
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  readonly crumbs = toSignal(
    this.router.events.pipe(
      filter((e) => e instanceof NavigationEnd),
      startWith(null),
      map(() => this.buildCrumbs()),
    ),
    { initialValue: [] as Crumb[] },
  );

  private buildCrumbs(): Crumb[] {
    const crumbs: Crumb[] = [];
    let current: ActivatedRoute | null = this.route.root;
    let url = '';

    while (current?.snapshot) {
      const snapshot = current.snapshot;
      const path = snapshot.routeConfig?.path;
      if (path && path !== '' && !path.includes(':')) {
        url += `/${path}`;
        const label =
          (snapshot.data['breadcrumb'] as string | undefined) ??
          path.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
        if (path !== 'app') {
          crumbs.push({ label, url: url.startsWith('/app') ? url : `/app${url}` });
        }
      }
      current = current.firstChild ?? null;
    }

    return crumbs.filter((c) => c.url !== '/app/dashboard' || crumbs.length === 1);
  }
}
