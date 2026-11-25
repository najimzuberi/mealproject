import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { MealListQuery, ReportingAssignment, Submission } from '../models/meal.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type ReportingAssignmentPayload = Partial<
  Pick<
    ReportingAssignment,
    | 'monitoring_plan_id'
    | 'monitoring_plan_item_id'
    | 'reporting_period_id'
    | 'assigned_to'
    | 'supervisor_id'
    | 'due_on'
    | 'status'
  >
>;

export type SubmissionPayload = Partial<
  Pick<
    Submission,
    | 'reporting_assignment_id'
    | 'monitoring_plan_id'
    | 'monitoring_plan_item_id'
    | 'indicator_id'
    | 'reporting_period_id'
    | 'numerator'
    | 'denominator'
    | 'value'
    | 'disaggregations'
    | 'notes'
    | 'status'
  >
>;

export type WorkflowCommentPayload = {
  comment?: string | null;
};

/** Empty page used when data-collection APIs are not yet available (404). */
export function emptyMealPage<T>(): Paginated<T> {
  return {
    items: [],
    pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1 },
  };
}

function tolerateNotFoundList<T>(): (err: unknown) => Observable<Paginated<T>> {
  return (err: unknown) => {
    if (err instanceof HttpErrorResponse && err.status === 404) {
      return of(emptyMealPage<T>());
    }
    return throwError(() => err);
  };
}

@Injectable({ providedIn: 'root' })
export class SubmissionService {
  private readonly http = inject(HttpClient);
  private readonly assignmentsBase = `${environment.apiBaseUrl}/reporting-assignments`;
  private readonly submissionsBase = `${environment.apiBaseUrl}/submissions`;

  listAssignments(query?: MealListQuery): Observable<Paginated<ReportingAssignment>> {
    return this.http
      .get<ApiResponse<ReportingAssignment[] | Paginated<ReportingAssignment>>>(this.assignmentsBase, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<ReportingAssignment>()),
      );
  }

  createAssignment(payload: ReportingAssignmentPayload): Observable<ReportingAssignment> {
    return this.http
      .post<ApiResponse<ReportingAssignment>>(this.assignmentsBase, payload)
      .pipe(map((res) => res.data));
  }

  listSubmissions(query?: MealListQuery): Observable<Paginated<Submission>> {
    return this.http
      .get<ApiResponse<Submission[] | Paginated<Submission>>>(this.submissionsBase, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<Submission>()),
      );
  }

  getSubmission(id: number): Observable<Submission> {
    return this.http
      .get<ApiResponse<Submission>>(`${this.submissionsBase}/${id}`)
      .pipe(map((res) => res.data));
  }

  createSubmission(payload: SubmissionPayload): Observable<Submission> {
    return this.http
      .post<ApiResponse<Submission>>(this.submissionsBase, payload)
      .pipe(map((res) => res.data));
  }

  updateSubmission(id: number, payload: SubmissionPayload): Observable<Submission> {
    return this.http
      .put<ApiResponse<Submission>>(`${this.submissionsBase}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  submit(id: number, payload?: WorkflowCommentPayload): Observable<Submission> {
    return this.http
      .post<ApiResponse<Submission>>(`${this.submissionsBase}/${id}/submit`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  returnSubmission(id: number, payload?: WorkflowCommentPayload): Observable<Submission> {
    return this.http
      .post<ApiResponse<Submission>>(`${this.submissionsBase}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  verify(id: number, payload?: WorkflowCommentPayload): Observable<Submission> {
    return this.http
      .post<ApiResponse<Submission>>(`${this.submissionsBase}/${id}/verify`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  validate(id: number, payload?: WorkflowCommentPayload): Observable<Submission> {
    return this.http
      .post<ApiResponse<Submission>>(`${this.submissionsBase}/${id}/validate`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<Submission> {
    return this.http
      .post<ApiResponse<Submission>>(`${this.submissionsBase}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  publish(id: number, payload?: WorkflowCommentPayload): Observable<Submission> {
    return this.http
      .post<ApiResponse<Submission>>(`${this.submissionsBase}/${id}/publish`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  importFile(file: File, meta?: Record<string, string>): Observable<Record<string, unknown>> {
    const formData = new FormData();
    formData.append('file', file);
    if (meta) {
      for (const [key, value] of Object.entries(meta)) {
        formData.append(key, value);
      }
    }
    return this.http
      .post<ApiResponse<Record<string, unknown>>>(`${this.submissionsBase}/import`, formData)
      .pipe(map((res) => res.data));
  }
}
