import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, catchError, map, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { PerformanceListQuery, Report, ReportStatus, ReportType } from '../models/performance.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type ReportPayload = Partial<
  Pick<
    Report,
    | 'title'
    | 'report_type'
    | 'status'
    | 'programme_id'
    | 'project_id'
    | 'reporting_period_id'
    | 'starts_on'
    | 'ends_on'
    | 'executive_summary'
    | 'performance_summary'
    | 'challenges'
    | 'risks'
    | 'recommendations'
    | 'ai_assisted'
  >
> & {
  report_type?: ReportType;
  status?: ReportStatus;
};

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
export class ReportService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/reports`;

  list(query?: PerformanceListQuery): Observable<Paginated<Report>> {
    return this.http
      .get<ApiResponse<Report[] | Paginated<Report>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(
        map((res) => unwrapPaginated(res.data)),
        catchError(tolerateNotFoundList<Report>()),
      );
  }

  get(id: number): Observable<Report> {
    return this.http.get<ApiResponse<Report>>(`${this.base}/${id}`).pipe(map((res) => res.data));
  }

  create(payload: ReportPayload): Observable<Report> {
    return this.http
      .post<ApiResponse<Report>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: ReportPayload): Observable<Report> {
    return this.http
      .put<ApiResponse<Report>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  submit(id: number, payload?: WorkflowCommentPayload): Observable<Report> {
    return this.http
      .post<ApiResponse<Report>>(`${this.base}/${id}/submit`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  returnReport(id: number, payload?: WorkflowCommentPayload): Observable<Report> {
    return this.http
      .post<ApiResponse<Report>>(`${this.base}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  approve(id: number, payload?: WorkflowCommentPayload): Observable<Report> {
    return this.http
      .post<ApiResponse<Report>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  publish(id: number, payload?: WorkflowCommentPayload): Observable<Report> {
    return this.http
      .post<ApiResponse<Report>>(`${this.base}/${id}/publish`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  exportPdf(id: number): Observable<Blob> {
    return this.http.get(`${this.base}/${id}/export/pdf`, { responseType: 'blob' });
  }

  exportExcel(id: number): Observable<Blob> {
    return this.http.get(`${this.base}/${id}/export/excel`, { responseType: 'blob' });
  }
}
