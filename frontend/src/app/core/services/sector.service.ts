import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { ListQuery, Sector } from '../models/organization.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

@Injectable({ providedIn: 'root' })
export class SectorService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/sectors`;

  list(query?: ListQuery): Observable<Paginated<Sector>> {
    return this.http
      .get<ApiResponse<Sector[] | Paginated<Sector>>>(this.base, { params: toHttpParams(query) })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }
}
