import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { unwrapList, unwrapPaginated } from '../utils/api.util';

export interface OrgRole {
  id: number;
  name: string;
  display_name?: string | null;
  is_system?: boolean;
  permissions?: string[];
}

@Injectable({ providedIn: 'root' })
export class RoleService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/roles`;

  list(): Observable<Paginated<OrgRole>> {
    return this.http
      .get<ApiResponse<OrgRole[] | Paginated<OrgRole>>>(this.base)
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  permissions(): Observable<string[]> {
    return this.http
      .get<ApiResponse<string[]>>(`${environment.apiBaseUrl}/permissions`)
      .pipe(map((res) => unwrapList(res.data)));
  }
}
