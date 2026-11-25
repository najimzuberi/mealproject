import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { KnowledgeDocument, LearningListQuery } from '../models/learning.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type KnowledgeDocumentPayload = Partial<
  Pick<
    KnowledgeDocument,
    | 'code'
    | 'title'
    | 'category'
    | 'description'
    | 'document_type'
    | 'tags'
    | 'keywords'
    | 'project_id'
    | 'programme_id'
    | 'department_id'
    | 'author_id'
    | 'published_on'
    | 'access_level'
    | 'status'
    | 'ai_summary'
    | 'ai_assisted'
  >
>;

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
export class KnowledgeService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/knowledge-documents`;

  list(query?: LearningListQuery): Observable<Paginated<KnowledgeDocument>> {
    return this.http
      .get<ApiResponse<KnowledgeDocument[] | Paginated<KnowledgeDocument>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<KnowledgeDocument>()),
      );
  }

  get(id: number): Observable<KnowledgeDocument> {
    return this.http
      .get<ApiResponse<KnowledgeDocument>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: KnowledgeDocumentPayload): Observable<KnowledgeDocument> {
    return this.http
      .post<ApiResponse<KnowledgeDocument>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: KnowledgeDocumentPayload): Observable<KnowledgeDocument> {
    return this.http
      .put<ApiResponse<KnowledgeDocument>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  upload(id: number, file: File, meta?: Record<string, string>): Observable<KnowledgeDocument> {
    const formData = new FormData();
    formData.append('file', file);
    if (meta) {
      for (const [key, value] of Object.entries(meta)) {
        formData.append(key, value);
      }
    }
    return this.http
      .post<ApiResponse<KnowledgeDocument>>(`${this.base}/${id}/upload`, formData)
      .pipe(map((res) => res.data));
  }

  download(id: number): Observable<Blob> {
    return this.http.get(`${this.base}/${id}/download`, { responseType: 'blob' });
  }

  archive(id: number): Observable<KnowledgeDocument> {
    return this.http
      .post<ApiResponse<KnowledgeDocument>>(`${this.base}/${id}/archive`, {})
      .pipe(map((res) => res.data));
  }
}
