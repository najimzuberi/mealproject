import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { MealListQuery, MonitoringPlan, MonitoringPlanItem } from '../models/meal.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type MonitoringPlanPayload = Partial<
  Pick<
    MonitoringPlan,
    | 'project_id'
    | 'code'
    | 'title'
    | 'description'
    | 'intended_use'
    | 'assumptions'
    | 'limitations'
  >
> & {
  items?: Array<Partial<MonitoringPlanItem> & { indicator_id: number }>;
};

export type WorkflowCommentPayload = {
  comment?: string | null;
};

@Injectable({ providedIn: 'root' })
export class MonitoringPlanService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/monitoring-plans`;

  list(query?: MealListQuery): Observable<Paginated<MonitoringPlan>> {
    const params = { ...query };
    if (params?.search && !params.q) {
      params.q = params.search;
      delete params.search;
    }
    return this.http
      .get<ApiResponse<MonitoringPlan[] | Paginated<MonitoringPlan>>>(this.base, {
        params: toHttpParams(params),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<MonitoringPlan> {
    return this.http
      .get<ApiResponse<MonitoringPlan>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: MonitoringPlanPayload): Observable<MonitoringPlan> {
    return this.http
      .post<ApiResponse<MonitoringPlan>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: MonitoringPlanPayload): Observable<MonitoringPlan> {
    return this.http
      .put<ApiResponse<MonitoringPlan>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  submit(id: number, payload?: WorkflowCommentPayload): Observable<MonitoringPlan> {
    return this.http
      .post<ApiResponse<MonitoringPlan>>(`${this.base}/${id}/submit`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  returnPlan(id: number, payload?: WorkflowCommentPayload): Observable<MonitoringPlan> {
    return this.http
      .post<ApiResponse<MonitoringPlan>>(`${this.base}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<MonitoringPlan> {
    return this.http
      .post<ApiResponse<MonitoringPlan>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  activate(id: number, payload?: WorkflowCommentPayload): Observable<MonitoringPlan> {
    return this.http
      .post<ApiResponse<MonitoringPlan>>(`${this.base}/${id}/activate`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  archive(id: number, payload?: WorkflowCommentPayload): Observable<MonitoringPlan> {
    return this.http
      .post<ApiResponse<MonitoringPlan>>(`${this.base}/${id}/archive`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  versions(id: number): Observable<MonitoringPlan[]> {
    return this.http
      .get<ApiResponse<MonitoringPlan[] | Paginated<MonitoringPlan>>>(`${this.base}/${id}/versions`)
      .pipe(map((res) => unwrapPaginated(res.data).items));
  }
}
