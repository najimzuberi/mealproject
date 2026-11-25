import { Component, input } from '@angular/core';
import { MatCardModule } from '@angular/material/card';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-objectives-progress-widget',
  standalone: true,
  imports: [MatCardModule, EmptyStateComponent],
  template: `
    <mat-card appearance="outlined" class="dash-widget">
      <div class="dash-widget__header">
        <div>
          <h3 class="dash-widget__title">Strategic Objectives Progress</h3>
          <p class="dash-widget__subtitle">Progress against strategic goals and objectives</p>
        </div>
      </div>

      <div class="dash-widget__body">
        <app-empty-state
          [title]="available() ? 'No objectives yet' : 'Performance data is not available yet.'"
          [message]="message()"
          icon="track_changes"
        />
      </div>
    </mat-card>
  `,
  styleUrl: './objectives-progress-widget.scss',
})
export class ObjectivesProgressWidgetComponent {
  readonly available = input(false);
  readonly message = input('Objective progress metrics require Phase 3 performance data.');
}
