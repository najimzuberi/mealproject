import { Component, input, output } from '@angular/core';
import { MatCardModule } from '@angular/material/card';
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';

@Component({
  selector: 'app-stat-card',
  standalone: true,
  imports: [MatCardModule, MatIconModule, MatTooltipModule],
  templateUrl: './stat-card.html',
  styleUrl: './stat-card.scss',
})
export class StatCardComponent {
  readonly label = input.required<string>();
  readonly value = input<string | number>('—');
  readonly icon = input('insights');
  readonly hint = input<string | null>(null);
  readonly supportingText = input<string | null>(null);
  readonly trend = input<string | null>(null);
  readonly loading = input(false);
  readonly unavailable = input(false);
  readonly clicked = output<void>();

  onActivate(): void {
    if (this.unavailable() || this.loading()) {
      return;
    }
    this.clicked.emit();
  }
}
