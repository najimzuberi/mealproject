import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { ListQuery, ReportingCalendar, ReportingPeriod } from '../models/organization.model';
import { toHttpParams, unwrapList, unwrapPaginated } from '../utils/api.util';

export type ReportingCalendarPayload = Partial<
  Pick<ReportingCalendar, 'name' | 'frequency' | 'description' | 'allow_overlapping_periods' | 'status'>
> & {
  periods?: Array<{
    name: string;
    starts_on: string;
    ends_on: string;
    status?: string;
  }>;
};

export type ReportingPeriodPayload = {
  name: string;
  starts_on: string;
  ends_on: string;
  status?: string;
};

@Injectable({ providedIn: 'root' })
export class ReportingCalendarService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/reporting-calendars`;

  list(query?: ListQuery): Observable<Paginated<ReportingCalendar>> {
    return this.http
      .get<ApiResponse<ReportingCalendar[] | Paginated<ReportingCalendar>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<ReportingCalendar> {
    return this.http
      .get<ApiResponse<ReportingCalendar>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: ReportingCalendarPayload): Observable<ReportingCalendar> {
    return this.http
      .post<ApiResponse<ReportingCalendar>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: ReportingCalendarPayload): Observable<ReportingCalendar> {
    return this.http
      .put<ApiResponse<ReportingCalendar>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  listPeriods(calendarId: number): Observable<ReportingPeriod[]> {
    return this.http
      .get<ApiResponse<ReportingPeriod[] | Paginated<ReportingPeriod>>>(
        `${this.base}/${calendarId}/periods`,
      )
      .pipe(map((res) => unwrapList(res.data)));
  }

  createPeriod(calendarId: number, payload: ReportingPeriodPayload): Observable<ReportingPeriod> {
    return this.http
      .post<ApiResponse<ReportingPeriod>>(`${this.base}/${calendarId}/periods`, payload)
      .pipe(map((res) => res.data));
  }

  openPeriod(periodId: number): Observable<ReportingPeriod> {
    return this.http
      .post<ApiResponse<ReportingPeriod>>(
        `${environment.apiBaseUrl}/reporting-periods/${periodId}/open`,
        {},
      )
      .pipe(map((res) => res.data));
  }

  closePeriod(periodId: number): Observable<ReportingPeriod> {
    return this.http
      .post<ApiResponse<ReportingPeriod>>(
        `${environment.apiBaseUrl}/reporting-periods/${periodId}/close`,
        {},
      )
      .pipe(map((res) => res.data));
  }
}
