import { Component, input } from '@angular/core';
import { MatChipsModule } from '@angular/material/chips';

@Component({
  selector: 'app-status-chip',
  standalone: true,
  imports: [MatChipsModule],
  template: `
    <mat-chip [class]="'status-chip status-chip--' + normalized()">{{ label() || status() }}</mat-chip>
  `,
  styles: `
    .status-chip {
      min-height: 1.6rem !important;
      font-size: 0.72rem !important;
      font-weight: 650;
      text-transform: capitalize;
    }
    .status-chip--active {
      background: color-mix(in srgb, var(--mm-secondary) 16%, white) !important;
      color: var(--mm-secondary) !important;
    }
    .status-chip--inactive,
    .status-chip--closed,
    .status-chip--archived {
      background: #e2e8f0 !important;
      color: #475569 !important;
    }
    .status-chip--pending,
    .status-chip--draft,
    .status-chip--open {
      background: color-mix(in srgb, var(--mm-accent) 18%, white) !important;
      color: #8a6d2f !important;
    }
  `,
})
export class StatusChipComponent {
  readonly status = input.required<string>();
  readonly label = input<string | null>(null);

  normalized(): string {
    return (this.status() || 'unknown').toLowerCase().replace(/\s+/g, '_');
  }
}
