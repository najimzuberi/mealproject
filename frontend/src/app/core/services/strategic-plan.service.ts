import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  StrategicGoal,
  StrategicPlan,
  StrategicPlanPriority,
  StrategyDocument,
  StrategyListQuery,
} from '../models/strategy.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type StrategicPlanPayload = Partial<
  Pick<
    StrategicPlan,
    | 'code'
    | 'title'
    | 'description'
    | 'vision'
    | 'mission'
    | 'strategic_context'
    | 'core_values'
    | 'start_date'
    | 'end_date'
    | 'owner_user_id'
    | 'department_id'
    | 'themes'
    | 'priorities'
    | 'goals'
  >
> & {
  goals?: StrategicGoal[];
  priorities?: StrategicPlanPriority[];
  core_values?: string[] | null;
};

export type WorkflowCommentPayload = {
  comment?: string | null;
};

@Injectable({ providedIn: 'root' })
export class StrategicPlanService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/strategic-plans`;

  list(query?: StrategyListQuery): Observable<Paginated<StrategicPlan>> {
    return this.http
      .get<ApiResponse<StrategicPlan[] | Paginated<StrategicPlan>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<StrategicPlan> {
    return this.http
      .get<ApiResponse<StrategicPlan>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: StrategicPlanPayload): Observable<StrategicPlan> {
    return this.http
      .post<ApiResponse<StrategicPlan>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: StrategicPlanPayload): Observable<StrategicPlan> {
    return this.http
      .put<ApiResponse<StrategicPlan>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }

  submit(id: number): Observable<StrategicPlan> {
    return this.http
      .post<ApiResponse<StrategicPlan>>(`${this.base}/${id}/submit`, {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<StrategicPlan> {
    return this.http
      .post<ApiResponse<StrategicPlan>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  activate(id: number): Observable<StrategicPlan> {
    return this.http
      .post<ApiResponse<StrategicPlan>>(`${this.base}/${id}/activate`, {})
      .pipe(map((res) => res.data));
  }

  archive(id: number): Observable<StrategicPlan> {
    return this.http
      .post<ApiResponse<StrategicPlan>>(`${this.base}/${id}/archive`, {})
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — may land with return workflow enhancements. */
  returnPlan(id: number, payload?: WorkflowCommentPayload): Observable<StrategicPlan> {
    return this.http
      .post<ApiResponse<StrategicPlan>>(`${this.base}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — may land with versioning enhancements. */
  duplicate(id: number, payload?: StrategicPlanPayload): Observable<StrategicPlan> {
    return this.http
      .post<ApiResponse<StrategicPlan>>(`${this.base}/${id}/duplicate`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — may land with versioning enhancements. */
  versions(id: number): Observable<StrategicPlan[]> {
    return this.http
      .get<ApiResponse<StrategicPlan[] | Paginated<StrategicPlan>>>(`${this.base}/${id}/versions`)
      .pipe(map((res) => unwrapPaginated(res.data).items));
  }

  /** Optional endpoint — may land with document enhancements. */
  uploadDocument(id: number, file: File, meta?: Record<string, string>): Observable<StrategyDocument> {
    const formData = new FormData();
    formData.append('file', file);
    if (meta) {
      for (const [key, value] of Object.entries(meta)) {
        formData.append(key, value);
      }
    }
    return this.http
      .post<ApiResponse<StrategyDocument>>(`${this.base}/${id}/documents`, formData)
      .pipe(map((res) => res.data));
  }

  /** Optional hierarchy endpoint; falls back callers should use get(). */
  hierarchy(id: number): Observable<StrategicPlan> {
    return this.http
      .get<ApiResponse<StrategicPlan>>(`${this.base}/${id}/hierarchy`)
      .pipe(map((res) => res.data));
  }

  /** Optional dedicated goals endpoint. */
  createGoal(planId: number, payload: Partial<StrategicGoal>): Observable<StrategicGoal> {
    return this.http
      .post<ApiResponse<StrategicGoal>>(`${this.base}/${planId}/goals`, payload)
      .pipe(map((res) => res.data));
  }

  /** Optional dedicated goals endpoint. */
  updateGoal(
    planId: number,
    goalId: number,
    payload: Partial<StrategicGoal>,
  ): Observable<StrategicGoal> {
    return this.http
      .put<ApiResponse<StrategicGoal>>(`${this.base}/${planId}/goals/${goalId}`, payload)
      .pipe(map((res) => res.data));
  }

  /** Optional dedicated goals endpoint. */
  deleteGoal(planId: number, goalId: number): Observable<void> {
    return this.http
      .delete<ApiResponse<null>>(`${this.base}/${planId}/goals/${goalId}`)
      .pipe(map(() => undefined));
  }
}
