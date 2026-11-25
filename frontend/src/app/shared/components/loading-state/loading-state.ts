import { Component, input } from '@angular/core';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';

@Component({
  selector: 'app-loading-state',
  standalone: true,
  imports: [MatProgressSpinnerModule],
  template: `
    <div class="loading" role="status" aria-live="polite">
      <mat-spinner diameter="36"></mat-spinner>
      <p>{{ message() }}</p>
    </div>
  `,
  styles: `
    .loading {
      display: grid;
      justify-items: center;
      gap: 0.75rem;
      padding: 2rem;
      color: var(--mm-body);
    }
  `,
})
export class LoadingStateComponent {
  readonly message = input('Loading…');
}
