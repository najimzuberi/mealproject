import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, DashboardOverview } from '../models/api.model';

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private readonly http = inject(HttpClient);

  overview(): Observable<DashboardOverview> {
    return this.http
      .get<ApiResponse<DashboardOverview>>(`${environment.apiBaseUrl}/dashboard/overview`)
      .pipe(map((res) => res.data));
  }
}
