import { Component, input } from '@angular/core';

@Component({
  selector: 'app-page-header',
  standalone: true,
  template: `
    <header class="page-header">
      <h1>{{ title() }}</h1>
      @if (subtitle()) {
        <p>{{ subtitle() }}</p>
      }
    </header>
  `,
  styles: `
    .page-header {
      margin-bottom: 1.25rem;
    }
    h1 {
      margin: 0 0 0.35rem;
      color: var(--mm-heading);
      font-size: 1.5rem;
    }
    p {
      margin: 0;
      color: var(--mm-body);
    }
  `,
})
export class PageHeaderComponent {
  readonly title = input.required<string>();
  readonly subtitle = input<string | null>(null);
}
