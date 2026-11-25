import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  ProgrammeProjectListQuery,
  Result,
  ResultsFramework,
} from '../models/programme-project.model';
import { toHttpParams, unwrapList, unwrapPaginated } from '../utils/api.util';

export type ResultsFrameworkPayload = Partial<
  Pick<ResultsFramework, 'project_id' | 'title' | 'theory_of_change'>
> & {
  results?: Array<Partial<Result> & { children?: Array<Partial<Result>> }>;
};

export type ResultPayload = Partial<
  Pick<
    Result,
    'parent_id' | 'level' | 'code' | 'title' | 'description' | 'sort_order' | 'department_id'
  >
> & {
  assumptions?: Array<{ description: string }>;
  risks?: Array<{ description: string; likelihood?: string; impact?: string }>;
};

export type WorkflowCommentPayload = {
  comment?: string | null;
};

@Injectable({ providedIn: 'root' })
export class ResultsFrameworkService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/results-frameworks`;
  private readonly resultsBase = `${environment.apiBaseUrl}/results`;

  list(query?: ProgrammeProjectListQuery): Observable<Paginated<ResultsFramework>> {
    return this.http
      .get<ApiResponse<ResultsFramework[] | Paginated<ResultsFramework>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<ResultsFramework> {
    return this.http
      .get<ApiResponse<ResultsFramework>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: ResultsFrameworkPayload): Observable<ResultsFramework> {
    return this.http
      .post<ApiResponse<ResultsFramework>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: ResultsFrameworkPayload): Observable<ResultsFramework> {
    return this.http
      .put<ApiResponse<ResultsFramework>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  hierarchy(id: number): Observable<Result[]> {
    return this.http
      .get<ApiResponse<Result[] | Paginated<Result>>>(`${this.base}/${id}/hierarchy`)
      .pipe(map((res) => unwrapList(res.data)));
  }

  addResult(frameworkId: number, payload: ResultPayload): Observable<Result> {
    return this.http
      .post<ApiResponse<Result>>(`${this.base}/${frameworkId}/results`, payload)
      .pipe(map((res) => res.data));
  }

  updateResult(resultId: number, payload: ResultPayload): Observable<Result> {
    return this.http
      .put<ApiResponse<Result>>(`${this.resultsBase}/${resultId}`, payload)
      .pipe(map((res) => res.data));
  }

  linkIndicator(resultId: number, indicatorId: number): Observable<Record<string, unknown>> {
    return this.http
      .post<ApiResponse<Record<string, unknown>>>(`${this.resultsBase}/${resultId}/link-indicator`, {
        indicator_id: indicatorId,
      })
      .pipe(map((res) => res.data));
  }

  submit(id: number, payload?: WorkflowCommentPayload): Observable<ResultsFramework> {
    return this.http
      .post<ApiResponse<ResultsFramework>>(`${this.base}/${id}/submit`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<ResultsFramework> {
    return this.http
      .post<ApiResponse<ResultsFramework>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  activate(id: number, payload?: WorkflowCommentPayload): Observable<ResultsFramework> {
    return this.http
      .post<ApiResponse<ResultsFramework>>(`${this.base}/${id}/activate`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional — may land with RF return workflow. */
  returnFramework(id: number, payload?: WorkflowCommentPayload): Observable<ResultsFramework> {
    return this.http
      .post<ApiResponse<ResultsFramework>>(`${this.base}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional — may land with RF archive workflow. */
  archive(id: number, payload?: WorkflowCommentPayload): Observable<ResultsFramework> {
    return this.http
      .post<ApiResponse<ResultsFramework>>(`${this.base}/${id}/archive`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional — may land with RF versioning. */
  versions(id: number): Observable<ResultsFramework[]> {
    return this.http
      .get<ApiResponse<ResultsFramework[] | Paginated<ResultsFramework>>>(`${this.base}/${id}/versions`)
      .pipe(map((res) => unwrapPaginated(res.data).items));
  }

  version(id: number): Observable<ResultsFramework> {
    return this.http
      .post<ApiResponse<ResultsFramework>>(`${this.base}/${id}/version`, {})
      .pipe(map((res) => res.data));
  }
}
