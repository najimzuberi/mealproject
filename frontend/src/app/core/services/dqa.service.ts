import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { DqaAssessment, DqaIssue, MealListQuery } from '../models/meal.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';
import { emptyMealPage } from './submission.service';

export type DqaAssessmentPayload = Partial<
  Pick<
    DqaAssessment,
    | 'submission_id'
    | 'status'
    | 'validity_score'
    | 'reliability_score'
    | 'completeness_score'
    | 'timeliness_score'
    | 'precision_score'
    | 'integrity_score'
    | 'overall_score'
    | 'summary'
  >
>;

export type DqaIssuePayload = Partial<
  Pick<
    DqaIssue,
    | 'dqa_assessment_id'
    | 'submission_id'
    | 'assigned_to'
    | 'dimension'
    | 'severity'
    | 'title'
    | 'description'
    | 'status'
    | 'resolution_notes'
  >
>;

function tolerateNotFoundList<T>(): (err: unknown) => Observable<Paginated<T>> {
  return (err: unknown) => {
    if (err instanceof HttpErrorResponse && err.status === 404) {
      return of(emptyMealPage<T>());
    }
    return throwError(() => err);
  };
}

@Injectable({ providedIn: 'root' })
export class DqaService {
  private readonly http = inject(HttpClient);
  private readonly assessmentsBase = `${environment.apiBaseUrl}/dqa-assessments`;
  private readonly issuesBase = `${environment.apiBaseUrl}/dqa-issues`;

  listAssessments(query?: MealListQuery): Observable<Paginated<DqaAssessment>> {
    return this.http
      .get<ApiResponse<DqaAssessment[] | Paginated<DqaAssessment>>>(this.assessmentsBase, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<DqaAssessment>()),
      );
  }

  getAssessment(id: number): Observable<DqaAssessment> {
    return this.http
      .get<ApiResponse<DqaAssessment>>(`${this.assessmentsBase}/${id}`)
      .pipe(map((res) => res.data));
  }

  createAssessment(payload: DqaAssessmentPayload): Observable<DqaAssessment> {
    return this.http
      .post<ApiResponse<DqaAssessment>>(this.assessmentsBase, payload)
      .pipe(map((res) => res.data));
  }

  complete(id: number, payload?: Partial<DqaAssessmentPayload>): Observable<DqaAssessment> {
    return this.http
      .post<ApiResponse<DqaAssessment>>(`${this.assessmentsBase}/${id}/complete`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  listIssues(query?: MealListQuery): Observable<Paginated<DqaIssue>> {
    return this.http
      .get<ApiResponse<DqaIssue[] | Paginated<DqaIssue>>>(this.issuesBase, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<DqaIssue>()),
      );
  }

  createIssue(payload: DqaIssuePayload): Observable<DqaIssue> {
    return this.http
      .post<ApiResponse<DqaIssue>>(this.issuesBase, payload)
      .pipe(map((res) => res.data));
  }

  resolveIssue(id: number, payload?: Pick<DqaIssuePayload, 'resolution_notes' | 'status'>): Observable<DqaIssue> {
    return this.http
      .put<ApiResponse<DqaIssue>>(`${this.issuesBase}/${id}/resolve`, payload ?? {})
      .pipe(map((res) => res.data));
  }
}
