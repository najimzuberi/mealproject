import { Component, input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-empty-state',
  standalone: true,
  imports: [MatIconModule],
  template: `
    <div class="empty">
      <div class="empty__icon" aria-hidden="true">
        <mat-icon>{{ icon() }}</mat-icon>
      </div>
      <h3>{{ title() }}</h3>
      <p>{{ message() }}</p>
    </div>
  `,
  styles: `
    :host {
      display: block;
      width: 100%;
      height: 100%;
    }

    .empty {
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      width: 100%;
      min-height: 11rem;
      padding: 1.5rem 1rem;
      color: #64748b;
    }

    .empty__icon {
      width: 2.75rem;
      height: 2.75rem;
      border-radius: 0.75rem;
      display: grid;
      place-items: center;
      margin-bottom: 0.85rem;
      background: color-mix(in srgb, var(--mm-secondary) 10%, #ffffff);
      color: var(--mm-secondary);
    }

    mat-icon {
      font-size: 1.35rem;
      width: 1.35rem;
      height: 1.35rem;
      line-height: 1.35rem;
    }

    h3 {
      margin: 0 0 0.35rem;
      color: #172b43;
      font-size: 0.95rem;
      font-weight: 650;
      line-height: 1.35;
    }

    p {
      margin: 0;
      max-width: 22rem;
      font-size: 0.84rem;
      line-height: 1.45;
    }
  `,
})
export class EmptyStateComponent {
  readonly title = input('Nothing to show yet');
  readonly message = input('Data will appear here when available.');
  readonly icon = input('inbox');
}
