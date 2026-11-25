import { Component, input } from '@angular/core';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-coming-soon',
  standalone: true,
  imports: [MatCardModule, MatChipsModule, MatIconModule],
  template: `
    <mat-card appearance="outlined" class="soon">
      <mat-chip-set>
        <mat-chip>Coming soon</mat-chip>
      </mat-chip-set>
      <mat-icon>construction</mat-icon>
      <h2>{{ title() }}</h2>
      <p>{{ message() }}</p>
    </mat-card>
  `,
  styles: `
    .soon {
      padding: 2.5rem 1.5rem;
      text-align: center;
      max-width: 40rem;
      margin: 1rem auto;
    }
    mat-icon {
      font-size: 2.5rem;
      width: 2.5rem;
      height: 2.5rem;
      color: var(--mm-accent);
      margin: 1rem 0 0.5rem;
    }
    h2 {
      color: var(--mm-heading);
      margin: 0 0 0.5rem;
    }
    p {
      color: var(--mm-body);
      margin: 0;
    }
  `,
})
export class ComingSoonComponent {
  readonly title = input('Module in preparation');
  readonly message = input(
    'This capability is planned for a later MEAL MASTER phase and is not available yet.',
  );
}
