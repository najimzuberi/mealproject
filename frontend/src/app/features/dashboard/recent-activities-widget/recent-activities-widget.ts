import { Component, input } from '@angular/core';
import { DatePipe } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { DashboardOverview } from '../../../core/models/api.model';

@Component({
  selector: 'app-recent-activities-widget',
  standalone: true,
  imports: [DatePipe, MatCardModule, EmptyStateComponent],
  template: `
    <mat-card appearance="outlined" class="dash-widget">
      <div class="dash-widget__header">
        <div>
          <h3 class="dash-widget__title">Recent System Activities</h3>
          <p class="dash-widget__subtitle">Latest authorized actions across modules</p>
        </div>
      </div>

      <div class="dash-widget__body">
        @if (activities().length === 0) {
          <app-empty-state
            title="No recent activity"
            message="Authorized audit activity will appear here when available."
            icon="history"
          />
        } @else {
          <ul class="activity-list">
            @for (activity of activities(); track activity.id) {
              <li class="activity-list__item">
                <div class="activity-list__avatar" aria-hidden="true">{{ initials(activity.user_id) }}</div>
                <div class="activity-list__copy">
                  <strong>{{ activity.action }}</strong>
                  <span>
                    User {{ activity.user_id || '—' }}
                    ·
                    {{ activity.module }}
                    ·
                    {{ activity.created_at | date: 'short' }}
                  </span>
                </div>
              </li>
            }
          </ul>
        }
      </div>
    </mat-card>
  `,
  styleUrl: './recent-activities-widget.scss',
})
export class RecentActivitiesWidgetComponent {
  readonly activities = input<DashboardOverview['recent_activities']>([]);

  initials(userId: number | null): string {
    if (!userId) {
      return 'SY';
    }
    return `U${String(userId).slice(-1)}`;
  }
}
