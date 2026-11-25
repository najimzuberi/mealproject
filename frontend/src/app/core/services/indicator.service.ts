import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  Indicator,
  IndicatorBaseline,
  IndicatorDisaggregation,
  IndicatorTarget,
  MealListQuery,
} from '../models/meal.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type IndicatorPayload = Partial<
  Pick<
    Indicator,
    | 'code'
    | 'name'
    | 'description'
    | 'definition'
    | 'measurement_unit'
    | 'numerator'
    | 'denominator'
    | 'formula'
    | 'aggregation_method'
    | 'direction_of_change'
    | 'result_level'
    | 'strategic_objective_id'
    | 'project_id'
    | 'results_framework_id'
    | 'result_id'
    | 'indicator_type'
    | 'source_url'
    | 'source_organization'
    | 'source_accessed_on'
    | 'adaptation_notes'
    | 'data_source'
    | 'collection_method'
    | 'collection_frequency'
    | 'responsible_collector_id'
    | 'supervisor_id'
    | 'me_validator_id'
    | 'means_of_verification'
    | 'validation_rules'
    | 'limitations'
    | 'assumptions'
    | 'quality_checks'
  >
> & {
  baselines?: IndicatorBaseline[];
  targets?: IndicatorTarget[];
  disaggregations?: IndicatorDisaggregation[];
};

export type WorkflowCommentPayload = {
  comment?: string | null;
};

@Injectable({ providedIn: 'root' })
export class IndicatorService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/indicators`;

  list(query?: MealListQuery): Observable<Paginated<Indicator>> {
    const params = { ...query };
    if (params?.search && !params.q) {
      params.q = params.search;
      delete params.search;
    }
    return this.http
      .get<ApiResponse<Indicator[] | Paginated<Indicator>>>(this.base, {
        params: toHttpParams(params),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<Indicator> {
    return this.http
      .get<ApiResponse<Indicator>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: IndicatorPayload): Observable<Indicator> {
    return this.http
      .post<ApiResponse<Indicator>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: IndicatorPayload): Observable<Indicator> {
    return this.http
      .put<ApiResponse<Indicator>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  submit(id: number, payload?: WorkflowCommentPayload): Observable<Indicator> {
    return this.http
      .post<ApiResponse<Indicator>>(`${this.base}/${id}/submit`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<Indicator> {
    return this.http
      .post<ApiResponse<Indicator>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  retire(id: number, payload?: WorkflowCommentPayload): Observable<Indicator> {
    return this.http
      .post<ApiResponse<Indicator>>(`${this.base}/${id}/retire`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — may land with return workflow enhancements. */
  returnIndicator(id: number, payload?: WorkflowCommentPayload): Observable<Indicator> {
    return this.http
      .post<ApiResponse<Indicator>>(`${this.base}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  versions(id: number): Observable<Indicator[]> {
    return this.http
      .get<ApiResponse<Indicator[] | Paginated<Indicator>>>(`${this.base}/${id}/versions`)
      .pipe(map((res) => unwrapPaginated(res.data).items));
  }
}
