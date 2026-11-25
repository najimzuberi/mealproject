import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  FeedbackCase,
  FeedbackCaseSummary,
  FeedbackCategory,
  LearningListQuery,
} from '../models/learning.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type FeedbackCasePayload = Partial<
  Pick<
    FeedbackCase,
    | 'feedback_type'
    | 'feedback_category_id'
    | 'description'
    | 'submission_channel'
    | 'project_id'
    | 'geographic_unit_id'
    | 'is_anonymous'
    | 'complainant_name'
    | 'complainant_contact'
    | 'consent_given'
    | 'confidentiality_level'
    | 'priority'
    | 'assigned_to'
    | 'responsible_department_id'
    | 'supervisor_id'
    | 'resolution_deadline'
  >
>;

export type FeedbackCategoryPayload = Partial<
  Pick<FeedbackCategory, 'name' | 'code' | 'is_sensitive' | 'is_active'>
>;

export type FeedbackAssignPayload = {
  assigned_to?: number | null;
  responsible_department_id?: number | null;
  supervisor_id?: number | null;
  comment?: string | null;
};

export type FeedbackEscalatePayload = {
  escalation_reason?: string | null;
  assigned_to?: number | null;
  supervisor_id?: number | null;
};

export type FeedbackResolvePayload = {
  resolution_description?: string | null;
  action_taken?: string | null;
  feedback_loop_status?: string | null;
};

export type FeedbackClosePayload = {
  comment?: string | null;
};

function emptyPage<T>(): Paginated<T> {
  return {
    items: [],
    pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1 },
  };
}

function tolerateNotFoundList<T>(): (err: unknown) => Observable<Paginated<T>> {
  return (err: unknown) => {
    if (err instanceof HttpErrorResponse && err.status === 404) {
      return of(emptyPage<T>());
    }
    return throwError(() => err);
  };
}

@Injectable({ providedIn: 'root' })
export class FeedbackService {
  private readonly http = inject(HttpClient);
  private readonly casesBase = `${environment.apiBaseUrl}/feedback-cases`;
  private readonly categoriesBase = `${environment.apiBaseUrl}/feedback-categories`;

  list(query?: LearningListQuery): Observable<Paginated<FeedbackCaseSummary>> {
    return this.http
      .get<ApiResponse<FeedbackCaseSummary[] | Paginated<FeedbackCaseSummary>>>(this.casesBase, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<FeedbackCaseSummary>()),
      );
  }

  get(id: number): Observable<FeedbackCase> {
    return this.http
      .get<ApiResponse<FeedbackCase>>(`${this.casesBase}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: FeedbackCasePayload): Observable<FeedbackCase> {
    return this.http
      .post<ApiResponse<FeedbackCase>>(this.casesBase, payload)
      .pipe(map((res) => res.data));
  }

  assign(id: number, payload: FeedbackAssignPayload): Observable<FeedbackCase> {
    return this.http
      .post<ApiResponse<FeedbackCase>>(`${this.casesBase}/${id}/assign`, payload)
      .pipe(map((res) => res.data));
  }

  escalate(id: number, payload: FeedbackEscalatePayload): Observable<FeedbackCase> {
    return this.http
      .post<ApiResponse<FeedbackCase>>(`${this.casesBase}/${id}/escalate`, payload)
      .pipe(map((res) => res.data));
  }

  resolve(id: number, payload: FeedbackResolvePayload): Observable<FeedbackCase> {
    return this.http
      .post<ApiResponse<FeedbackCase>>(`${this.casesBase}/${id}/resolve`, payload)
      .pipe(map((res) => res.data));
  }

  close(id: number, payload?: FeedbackClosePayload): Observable<FeedbackCase> {
    return this.http
      .post<ApiResponse<FeedbackCase>>(`${this.casesBase}/${id}/close`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  listCategories(query?: LearningListQuery): Observable<Paginated<FeedbackCategory>> {
    return this.http
      .get<ApiResponse<FeedbackCategory[] | Paginated<FeedbackCategory>>>(this.categoriesBase, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<FeedbackCategory>()),
      );
  }

  createCategory(payload: FeedbackCategoryPayload): Observable<FeedbackCategory> {
    return this.http
      .post<ApiResponse<FeedbackCategory>>(this.categoriesBase, payload)
      .pipe(map((res) => res.data));
  }
}
