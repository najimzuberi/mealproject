import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { Department, ListQuery } from '../models/organization.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type DepartmentPayload = Partial<
  Pick<Department, 'name' | 'code' | 'description' | 'status' | 'parent_id' | 'head_user_id' | 'email' | 'phone'>
>;

@Injectable({ providedIn: 'root' })
export class DepartmentService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/departments`;

  list(query?: ListQuery): Observable<Paginated<Department>> {
    return this.http
      .get<ApiResponse<Department[] | Paginated<Department>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<Department> {
    return this.http.get<ApiResponse<Department>>(`${this.base}/${id}`).pipe(map((res) => res.data));
  }

  create(payload: DepartmentPayload): Observable<Department> {
    return this.http
      .post<ApiResponse<Department>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: DepartmentPayload): Observable<Department> {
    return this.http
      .put<ApiResponse<Department>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }
}
