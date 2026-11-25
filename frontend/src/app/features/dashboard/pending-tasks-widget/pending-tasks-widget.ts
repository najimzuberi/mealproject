import { Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { DashboardOverview } from '../../../core/models/api.model';

@Component({
  selector: 'app-pending-tasks-widget',
  standalone: true,
  imports: [RouterLink, MatCardModule, MatButtonModule, EmptyStateComponent],
  template: `
    <mat-card appearance="outlined" class="dash-widget">
      <div class="dash-widget__header">
        <div>
          <h3 class="dash-widget__title">Pending Approvals</h3>
          <p class="dash-widget__subtitle">Items waiting for your review</p>
        </div>
      </div>

      <div class="dash-widget__body">
        @if (tasks().length === 0) {
          <app-empty-state
            title="You're all caught up"
            message="No approvals or tasks require your attention."
            icon="task_alt"
          />
        } @else {
          <ul class="approval-list">
            @for (task of tasks(); track task.type + '-' + task.id) {
              <li class="approval-list__item">
                <div class="approval-list__copy">
                  <strong>{{ task.title }}</strong>
                  <span>{{ formatType(task.type) }} · {{ task.status }}</span>
                </div>
                <a mat-stroked-button color="primary" [routerLink]="task.route">View</a>
              </li>
            }
          </ul>
        }
      </div>
    </mat-card>
  `,
  styleUrl: './pending-tasks-widget.scss',
})
export class PendingTasksWidgetComponent {
  readonly tasks = input<DashboardOverview['pending_tasks']>([]);

  formatType(type: string): string {
    return type.replace(/_/g, ' ');
  }
}
