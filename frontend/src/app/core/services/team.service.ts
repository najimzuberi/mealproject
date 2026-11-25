import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { ListQuery, Team } from '../models/organization.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type TeamPayload = Partial<
  Pick<
    Team,
    'department_id' | 'office_id' | 'leader_user_id' | 'name' | 'code' | 'description' | 'status'
  >
>;

@Injectable({ providedIn: 'root' })
export class TeamService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/teams`;

  list(query?: ListQuery): Observable<Paginated<Team>> {
    return this.http
      .get<ApiResponse<Team[] | Paginated<Team>>>(this.base, { params: toHttpParams(query) })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<Team> {
    return this.http.get<ApiResponse<Team>>(`${this.base}/${id}`).pipe(map((res) => res.data));
  }

  create(payload: TeamPayload): Observable<Team> {
    return this.http.post<ApiResponse<Team>>(this.base, payload).pipe(map((res) => res.data));
  }

  update(id: number, payload: TeamPayload): Observable<Team> {
    return this.http.put<ApiResponse<Team>>(`${this.base}/${id}`, payload).pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }

  syncMembers(id: number, userIds: number[]): Observable<Team> {
    return this.http
      .put<ApiResponse<Team>>(`${this.base}/${id}/members`, { user_ids: userIds })
      .pipe(map((res) => res.data));
  }
}
