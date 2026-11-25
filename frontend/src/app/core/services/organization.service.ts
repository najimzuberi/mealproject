import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, map, of } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, OrganizationSummary, Paginated } from '../models/api.model';
import { OrganizationProfile, OrganizationSetting } from '../models/organization.model';
import { toHttpParams, unwrapList, unwrapPaginated } from '../utils/api.util';
import { AuthService } from './auth.service';

@Injectable({ providedIn: 'root' })
export class OrganizationService {
  private readonly http = inject(HttpClient);
  private readonly auth = inject(AuthService);
  private readonly base = `${environment.apiBaseUrl}/organizations`;

  list(): Observable<OrganizationSummary[]> {
    return this.http
      .get<ApiResponse<OrganizationSummary[] | Paginated<OrganizationSummary>>>(this.base)
      .pipe(map((res) => unwrapList(res.data)));
  }

  get(id: number): Observable<OrganizationProfile> {
    return this.http
      .get<ApiResponse<OrganizationProfile>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: Partial<OrganizationProfile>): Observable<OrganizationProfile> {
    return this.http
      .put<ApiResponse<OrganizationProfile>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  uploadLogo(id: number, file: File): Observable<OrganizationProfile> {
    const form = new FormData();
    form.append('logo', file);
    return this.http
      .post<ApiResponse<OrganizationProfile>>(`${this.base}/${id}/logo`, form)
      .pipe(map((res) => res.data));
  }

  syncSectors(id: number, sectorIds: number[]): Observable<OrganizationProfile> {
    return this.http
      .put<ApiResponse<OrganizationProfile>>(`${this.base}/${id}/sectors`, { sector_ids: sectorIds })
      .pipe(map((res) => res.data));
  }

  getSettings(): Observable<OrganizationSetting[]> {
    return this.http
      .get<ApiResponse<OrganizationSetting[] | Paginated<OrganizationSetting>>>(
        `${environment.apiBaseUrl}/organization/settings`,
      )
      .pipe(
        map((res) => unwrapList(res.data)),
        catchError(() => {
          const orgId = this.auth.organizationId();
          if (!orgId) {
            return of([]);
          }
          return this.get(orgId).pipe(map((org) => org.settings ?? []));
        }),
      );
  }

  updateSettings(settings: OrganizationSetting[]): Observable<OrganizationProfile> {
    return this.http
      .put<ApiResponse<OrganizationProfile>>(`${environment.apiBaseUrl}/organization/settings`, {
        settings,
      })
      .pipe(map((res) => res.data));
  }

  activate(id: number): Observable<OrganizationProfile> {
    return this.http
      .post<ApiResponse<OrganizationProfile>>(`${this.base}/${id}/activate`, {})
      .pipe(map((res) => res.data));
  }

  deactivate(id: number): Observable<OrganizationProfile> {
    return this.http
      .post<ApiResponse<OrganizationProfile>>(`${this.base}/${id}/deactivate`, {})
      .pipe(map((res) => res.data));
  }

  platformList(query?: Record<string, string | number>): Observable<Paginated<OrganizationProfile>> {
    return this.http
      .get<ApiResponse<OrganizationProfile[] | Paginated<OrganizationProfile>>>(
        `${environment.apiBaseUrl}/platform/organizations`,
        { params: toHttpParams(query) },
      )
      .pipe(map((res) => unwrapPaginated(res.data)));
  }
}
