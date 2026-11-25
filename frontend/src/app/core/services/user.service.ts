import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { ListQuery, OrgUser } from '../models/organization.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type UserPayload = {
  name?: string;
  email?: string;
  phone?: string | null;
  password?: string;
  status?: string;
  membership_status?: string;
  role_ids?: number[];
};

@Injectable({ providedIn: 'root' })
export class UserService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/users`;

  list(query?: ListQuery): Observable<Paginated<OrgUser>> {
    return this.http
      .get<ApiResponse<OrgUser[] | Paginated<OrgUser>>>(this.base, { params: toHttpParams(query) })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<OrgUser> {
    return this.http.get<ApiResponse<OrgUser>>(`${this.base}/${id}`).pipe(map((res) => res.data));
  }

  create(payload: UserPayload): Observable<OrgUser> {
    return this.http.post<ApiResponse<OrgUser>>(this.base, payload).pipe(map((res) => res.data));
  }

  update(id: number, payload: UserPayload): Observable<OrgUser> {
    return this.http.put<ApiResponse<OrgUser>>(`${this.base}/${id}`, payload).pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }

  activate(id: number): Observable<OrgUser> {
    return this.http
      .post<ApiResponse<OrgUser>>(`${this.base}/${id}/activate`, {})
      .pipe(map((res) => res.data));
  }

  deactivate(id: number): Observable<OrgUser> {
    return this.http
      .post<ApiResponse<OrgUser>>(`${this.base}/${id}/deactivate`, {})
      .pipe(map((res) => res.data));
  }

  resetPassword(id: number, password: string, passwordConfirmation: string): Observable<void> {
    return this.http
      .post<ApiResponse<null>>(`${this.base}/${id}/reset-password`, {
        password,
        password_confirmation: passwordConfirmation,
      })
      .pipe(map(() => undefined));
  }

  assignRoles(id: number, roleIds: number[]): Observable<OrgUser> {
    return this.http
      .put<ApiResponse<OrgUser>>(`${this.base}/${id}/roles`, { role_ids: roleIds })
      .pipe(map((res) => res.data));
  }
}
