import { Component, input, output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-error-state',
  standalone: true,
  imports: [MatButtonModule, MatIconModule],
  template: `
    <div class="error" role="alert">
      <mat-icon>error_outline</mat-icon>
      <h3>{{ title() }}</h3>
      <p>{{ message() }}</p>
      @if (retryable()) {
        <button mat-stroked-button color="primary" type="button" (click)="retry.emit()">Retry</button>
      }
    </div>
  `,
  styles: `
    .error {
      text-align: center;
      padding: 1.5rem;
      color: var(--mm-body);
    }
    mat-icon {
      color: #b42318;
    }
    h3 {
      color: var(--mm-heading);
      margin: 0.35rem 0;
    }
  `,
})
export class ErrorStateComponent {
  readonly title = input('Unable to load data');
  readonly message = input('Please try again or contact support if the problem continues.');
  readonly retryable = input(true);
  readonly retry = output<void>();
}
