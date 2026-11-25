import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { toSignal } from '@angular/core/rxjs-interop';
import { map } from 'rxjs';
import { ComingSoonComponent } from '../../shared/components/coming-soon/coming-soon';
import { PageHeaderComponent } from '../../shared/components/page-header/page-header';
import { NAVIGATION_CONFIG } from '../../core/config/navigation.config';

@Component({
  selector: 'app-coming-soon-page',
  standalone: true,
  imports: [ComingSoonComponent, PageHeaderComponent],
  template: `
    <app-page-header [title]="title()" subtitle="This module is scheduled for a later release." />
    <app-coming-soon [title]="title()" />
  `,
})
export class ComingSoonPageComponent {
  private readonly route = inject(ActivatedRoute);

  readonly title = toSignal(
    this.route.paramMap.pipe(
      map((params) => {
        const id = params.get('moduleId');
        for (const group of NAVIGATION_CONFIG) {
          const item = group.items.find((i) => i.id === id);
          if (item) {
            return item.label;
          }
        }
        return id?.replace(/-/g, ' ') ?? 'Coming soon';
      }),
    ),
    { initialValue: 'Coming soon' },
  );
}
