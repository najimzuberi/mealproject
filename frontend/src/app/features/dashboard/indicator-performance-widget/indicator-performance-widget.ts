import { Component, input } from '@angular/core';
import { MatCardModule } from '@angular/material/card';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-indicator-performance-widget',
  standalone: true,
  imports: [MatCardModule, EmptyStateComponent],
  template: `
    <mat-card appearance="outlined" class="dash-widget">
      <div class="dash-widget__header">
        <div>
          <h3 class="dash-widget__title">Indicator Performance</h3>
          <p class="dash-widget__subtitle">Baseline, target and current values</p>
        </div>
      </div>

      <div class="dash-widget__body">
        <app-empty-state
          [title]="available() ? 'No performance series' : 'Performance data is not available yet.'"
          [message]="message()"
          icon="insights"
        />
      </div>
    </mat-card>
  `,
  styleUrl: './indicator-performance-widget.scss',
})
export class IndicatorPerformanceWidgetComponent {
  readonly available = input(false);
  readonly message = input('Indicator performance against targets requires Phase 3 collected results.');
}
