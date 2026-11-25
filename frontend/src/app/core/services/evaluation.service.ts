import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  Evaluation,
  EvaluationFinding,
  EvaluationRecommendation,
  LearningAction,
  LearningListQuery,
} from '../models/learning.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type EvaluationPayload = Partial<
  Pick<
    Evaluation,
    | 'code'
    | 'title'
    | 'evaluation_type'
    | 'programme_id'
    | 'project_id'
    | 'purpose'
    | 'scope'
    | 'starts_on'
    | 'ends_on'
    | 'evaluation_lead_id'
    | 'evaluation_team'
    | 'methodology'
    | 'data_collection_methods'
    | 'target_population'
    | 'sample_size'
    | 'geographic_coverage'
    | 'tools'
    | 'timeline_notes'
    | 'status'
  >
>;

export type EvaluationFindingPayload = Partial<
  Pick<
    EvaluationFinding,
    | 'reference'
    | 'title'
    | 'description'
    | 'supporting_evidence'
    | 'project_id'
    | 'result_id'
    | 'indicator_id'
    | 'conclusion'
    | 'significance'
  >
>;

export type EvaluationRecommendationPayload = Partial<
  Pick<
    EvaluationRecommendation,
    | 'evaluation_finding_id'
    | 'title'
    | 'description'
    | 'priority'
    | 'supporting_evidence'
    | 'responsible_department_id'
    | 'responsible_manager_id'
  >
>;

export type RecommendationRespondPayload = {
  management_response?: string | null;
  management_response_status?: string | null;
  response_date?: string | null;
  responsible_manager_id?: number | null;
};

export type LearningActionPayload = Partial<
  Pick<
    LearningAction,
    | 'actionable_type'
    | 'actionable_id'
    | 'description'
    | 'responsible_person_id'
    | 'department_id'
    | 'due_on'
    | 'required_evidence'
    | 'status'
    | 'progress_notes'
  >
>;

export type WorkflowCommentPayload = {
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
export class EvaluationService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/evaluations`;
  private readonly actionsBase = `${environment.apiBaseUrl}/learning-actions`;
  private readonly recommendationsBase = `${environment.apiBaseUrl}/evaluation-recommendations`;

  list(query?: LearningListQuery): Observable<Paginated<Evaluation>> {
    return this.http
      .get<ApiResponse<Evaluation[] | Paginated<Evaluation>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<Evaluation>()),
      );
  }

  get(id: number): Observable<Evaluation> {
    return this.http.get<ApiResponse<Evaluation>>(`${this.base}/${id}`).pipe(map((res) => res.data));
  }

  create(payload: EvaluationPayload): Observable<Evaluation> {
    return this.http
      .post<ApiResponse<Evaluation>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: EvaluationPayload): Observable<Evaluation> {
    return this.http
      .put<ApiResponse<Evaluation>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  submit(id: number, payload?: WorkflowCommentPayload): Observable<Evaluation> {
    return this.http
      .post<ApiResponse<Evaluation>>(`${this.base}/${id}/submit`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  returnEvaluation(id: number, payload?: WorkflowCommentPayload): Observable<Evaluation> {
    return this.http
      .post<ApiResponse<Evaluation>>(`${this.base}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<Evaluation> {
    return this.http
      .post<ApiResponse<Evaluation>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  complete(id: number, payload?: WorkflowCommentPayload): Observable<Evaluation> {
    return this.http
      .post<ApiResponse<Evaluation>>(`${this.base}/${id}/complete`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  addFinding(evaluationId: number, payload: EvaluationFindingPayload): Observable<EvaluationFinding> {
    return this.http
      .post<ApiResponse<EvaluationFinding>>(`${this.base}/${evaluationId}/findings`, payload)
      .pipe(map((res) => res.data));
  }

  addRecommendation(
    evaluationId: number,
    payload: EvaluationRecommendationPayload,
  ): Observable<EvaluationRecommendation> {
    return this.http
      .post<ApiResponse<EvaluationRecommendation>>(
        `${this.base}/${evaluationId}/recommendations`,
        payload,
      )
      .pipe(map((res) => res.data));
  }

  respondToRecommendation(
    recommendationId: number,
    payload: RecommendationRespondPayload,
  ): Observable<EvaluationRecommendation> {
    return this.http
      .post<ApiResponse<EvaluationRecommendation>>(
        `${this.recommendationsBase}/${recommendationId}/respond`,
        payload,
      )
      .pipe(map((res) => res.data));
  }

  listActions(query?: LearningListQuery): Observable<Paginated<LearningAction>> {
    return this.http
      .get<ApiResponse<LearningAction[] | Paginated<LearningAction>>>(this.actionsBase, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<LearningAction>()),
      );
  }

  createAction(payload: LearningActionPayload): Observable<LearningAction> {
    return this.http
      .post<ApiResponse<LearningAction>>(this.actionsBase, payload)
      .pipe(map((res) => res.data));
  }

  completeAction(
    id: number,
    payload?: Pick<LearningActionPayload, 'progress_notes' | 'status'>,
  ): Observable<LearningAction> {
    return this.http
      .post<ApiResponse<LearningAction>>(`${this.actionsBase}/${id}/complete`, payload ?? {})
      .pipe(map((res) => res.data));
  }
}
