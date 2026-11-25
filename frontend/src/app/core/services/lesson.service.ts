import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { LearningListQuery, Lesson } from '../models/learning.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type LessonPayload = Partial<
  Pick<
    Lesson,
    | 'reference'
    | 'title'
    | 'description'
    | 'source'
    | 'project_id'
    | 'programme_id'
    | 'evaluation_id'
    | 'feedback_case_id'
    | 'context'
    | 'challenge'
    | 'what_worked'
    | 'what_did_not_work'
    | 'recommendation'
    | 'responsible_person_id'
    | 'action_required'
    | 'status'
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
export class LessonService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/lessons`;

  list(query?: LearningListQuery): Observable<Paginated<Lesson>> {
    return this.http
      .get<ApiResponse<Lesson[] | Paginated<Lesson>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<Lesson>()),
      );
  }

  get(id: number): Observable<Lesson> {
    return this.http.get<ApiResponse<Lesson>>(`${this.base}/${id}`).pipe(map((res) => res.data));
  }

  create(payload: LessonPayload): Observable<Lesson> {
    return this.http.post<ApiResponse<Lesson>>(this.base, payload).pipe(map((res) => res.data));
  }

  update(id: number, payload: LessonPayload): Observable<Lesson> {
    return this.http
      .put<ApiResponse<Lesson>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  submit(id: number, payload?: WorkflowCommentPayload): Observable<Lesson> {
    return this.http
      .post<ApiResponse<Lesson>>(`${this.base}/${id}/submit`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<Lesson> {
    return this.http
      .post<ApiResponse<Lesson>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  assignAction(
    id: number,
    payload?: {
      description?: string | null;
      responsible_person_id?: number | null;
      department_id?: number | null;
      due_on?: string | null;
      required_evidence?: string | null;
    },
  ): Observable<Lesson> {
    return this.http
      .post<ApiResponse<Lesson>>(`${this.base}/${id}/assign-action`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  apply(id: number, payload?: WorkflowCommentPayload): Observable<Lesson> {
    return this.http
      .post<ApiResponse<Lesson>>(`${this.base}/${id}/apply`, payload ?? {})
      .pipe(map((res) => res.data));
  }
}
