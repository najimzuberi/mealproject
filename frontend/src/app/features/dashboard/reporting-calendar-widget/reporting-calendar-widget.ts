import { Component, input } from '@angular/core';
import { DatePipe, TitleCasePipe } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';
import { DashboardOverview } from '../../../core/models/api.model';

@Component({
  selector: 'app-reporting-calendar-widget',
  standalone: true,
  imports: [DatePipe, TitleCasePipe, MatCardModule, MatChipsModule, EmptyStateComponent],
  template: `
    <mat-card appearance="outlined" class="dash-widget">
      <div class="dash-widget__header">
        <div>
          <h3 class="dash-widget__title">Reporting Calendar</h3>
          <p class="dash-widget__subtitle">Upcoming, open, closed and overdue periods</p>
        </div>
      </div>

      <div class="dash-widget__body">
        @if (periods().length === 0) {
          <app-empty-state
            title="No reporting periods configured."
            message="Configure a reporting calendar to track reporting deadlines."
            icon="calendar_month"
          />
        } @else {
          <ul class="period-list">
            @for (period of periods(); track period.id) {
              <li class="period-list__item">
                <div class="period-list__copy">
                  <strong>{{ period.name }}</strong>
                  <span>
                    {{ period.calendar || 'Calendar' }}
                    ·
                    {{ period.starts_on | date: 'mediumDate' }}
                    –
                    {{ period.ends_on | date: 'mediumDate' }}
                  </span>
                </div>
                <mat-chip class="period-list__chip">{{ period.status | titlecase }}</mat-chip>
              </li>
            }
          </ul>
        }
      </div>
    </mat-card>
  `,
  styleUrl: './reporting-calendar-widget.scss',
})
export class ReportingCalendarWidgetComponent {
  readonly periods = input<DashboardOverview['reporting_periods']>([]);
}
