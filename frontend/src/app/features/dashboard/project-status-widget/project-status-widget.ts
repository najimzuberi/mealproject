import { Component, input } from '@angular/core';
import { MatCardModule } from '@angular/material/card';
import { BaseChartDirective } from 'ng2-charts';
import { ChartConfiguration } from 'chart.js';
import { EmptyStateComponent } from '../../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-project-status-widget',
  standalone: true,
  imports: [MatCardModule, BaseChartDirective, EmptyStateComponent],
  template: `
    <mat-card appearance="outlined" class="dash-widget">
      <div class="dash-widget__header">
        <div>
          <h3 class="dash-widget__title">Project Status Overview</h3>
          <p class="dash-widget__subtitle">Active, completed, on hold and draft projects</p>
        </div>
      </div>

      <div class="dash-widget__body">
        @if (hasData()) {
          <div class="dash-widget__chart">
            <canvas baseChart [data]="chartData()" [options]="options" [type]="'doughnut'"></canvas>
          </div>
        } @else {
          <app-empty-state
            title="No projects available yet."
            message="Create your first project to start tracking organizational performance."
            icon="work"
          />
        }
      </div>
    </mat-card>
  `,
  styleUrl: './project-status-widget.scss',
})
export class ProjectStatusWidgetComponent {
  readonly status = input<Record<string, number>>({});

  readonly options: ChartConfiguration<'doughnut'>['options'] = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: { boxWidth: 10, boxHeight: 10, padding: 14, font: { size: 12 } },
      },
    },
  };

  hasData(): boolean {
    return Object.values(this.status()).some((v) => v > 0);
  }

  chartData(): ChartConfiguration<'doughnut'>['data'] {
    const preferred = ['active', 'completed', 'on_hold', 'draft'];
    const source = this.status();
    const keys = [
      ...preferred.filter((key) => key in source),
      ...Object.keys(source).filter((key) => !preferred.includes(key)),
    ];

    return {
      labels: keys.map((key) => key.replace(/_/g, ' ')),
      datasets: [
        {
          data: keys.map((key) => source[key] ?? 0),
          backgroundColor: ['#117C78', '#142B45', '#D5AD55', '#94A3B8', '#CBD5E1'],
          borderWidth: 0,
        },
      ],
    };
  }
}
