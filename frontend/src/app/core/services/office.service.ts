import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { ListQuery, Office } from '../models/organization.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type OfficePayload = Partial<
  Pick<
    Office,
    | 'department_id'
    | 'parent_id'
    | 'geographic_unit_id'
    | 'manager_user_id'
    | 'name'
    | 'code'
    | 'type'
    | 'description'
    | 'address'
    | 'country'
    | 'region'
    | 'district'
    | 'contact_person'
    | 'email'
    | 'phone'
    | 'status'
  >
>;

@Injectable({ providedIn: 'root' })
export class OfficeService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/offices`;

  list(query?: ListQuery): Observable<Paginated<Office>> {
    return this.http
      .get<ApiResponse<Office[] | Paginated<Office>>>(this.base, { params: toHttpParams(query) })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<Office> {
    return this.http.get<ApiResponse<Office>>(`${this.base}/${id}`).pipe(map((res) => res.data));
  }

  create(payload: OfficePayload): Observable<Office> {
    return this.http.post<ApiResponse<Office>>(this.base, payload).pipe(map((res) => res.data));
  }

  update(id: number, payload: OfficePayload): Observable<Office> {
    return this.http
      .put<ApiResponse<Office>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }
}
