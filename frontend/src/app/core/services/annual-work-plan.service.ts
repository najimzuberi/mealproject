import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  AnnualWorkPlan,
  AwpPriority,
  BudgetLine,
  StrategyListQuery,
  sumBudgetEstimate,
} from '../models/strategy.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type AnnualWorkPlanPayload = Partial<
  Pick<
    AnnualWorkPlan,
    | 'strategic_plan_id'
    | 'code'
    | 'title'
    | 'planning_year'
    | 'description'
    | 'owner_user_id'
    | 'department_id'
    | 'priorities'
  >
> & {
  priorities?: AwpPriority[];
};

export type WorkflowCommentPayload = {
  comment?: string | null;
};

@Injectable({ providedIn: 'root' })
export class AnnualWorkPlanService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/annual-work-plans`;

  list(query?: StrategyListQuery): Observable<Paginated<AnnualWorkPlan>> {
    return this.http
      .get<ApiResponse<AnnualWorkPlan[] | Paginated<AnnualWorkPlan>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<AnnualWorkPlan> {
    return this.http
      .get<ApiResponse<AnnualWorkPlan>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: AnnualWorkPlanPayload): Observable<AnnualWorkPlan> {
    return this.http
      .post<ApiResponse<AnnualWorkPlan>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: AnnualWorkPlanPayload): Observable<AnnualWorkPlan> {
    return this.http
      .put<ApiResponse<AnnualWorkPlan>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }

  submit(id: number): Observable<AnnualWorkPlan> {
    return this.http
      .post<ApiResponse<AnnualWorkPlan>>(`${this.base}/${id}/submit`, {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<AnnualWorkPlan> {
    return this.http
      .post<ApiResponse<AnnualWorkPlan>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — may land with AWP activate workflow. */
  activate(id: number): Observable<AnnualWorkPlan> {
    return this.http
      .post<ApiResponse<AnnualWorkPlan>>(`${this.base}/${id}/activate`, {})
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — may land with return workflow enhancements. */
  returnPlan(id: number, payload?: WorkflowCommentPayload): Observable<AnnualWorkPlan> {
    return this.http
      .post<ApiResponse<AnnualWorkPlan>>(`${this.base}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — may land with AWP archive workflow. */
  archive(id: number): Observable<AnnualWorkPlan> {
    return this.http
      .post<ApiResponse<AnnualWorkPlan>>(`${this.base}/${id}/archive`, {})
      .pipe(map((res) => res.data));
  }

  /** Optional budget listing endpoint. */
  listBudgetLines(id: number): Observable<BudgetLine[]> {
    return this.http
      .get<ApiResponse<BudgetLine[] | Paginated<BudgetLine>>>(`${this.base}/${id}/budget-lines`)
      .pipe(map((res) => unwrapPaginated(res.data).items));
  }

  /** Optional budget summary endpoint; client can also sum local estimates. */
  budgetSummary(id: number): Observable<{ total: number; currency?: string | null; lines?: BudgetLine[] }> {
    return this.http
      .get<
        ApiResponse<{ total?: number; currency?: string | null; lines?: BudgetLine[] } | AnnualWorkPlan>
      >(`${this.base}/${id}/budget-summary`)
      .pipe(
        map((res) => {
          const data = res.data as {
            total?: number | string;
            total_budget?: number | string;
            currency?: string | null;
            lines?: BudgetLine[];
            priorities?: AnnualWorkPlan['priorities'];
            budget_summary?: AnnualWorkPlan['budget_summary'];
          };
          const rawTotal = data?.total ?? data?.total_budget;
          if (rawTotal !== undefined && rawTotal !== null && rawTotal !== '') {
            return {
              total: Number(rawTotal),
              currency: data.currency ?? null,
              lines: data.lines,
            };
          }
          return {
            total: sumBudgetEstimate(data as AnnualWorkPlan),
            currency: data?.budget_summary?.currency ?? null,
            lines: data?.lines,
          };
        }),
      );
  }
}
