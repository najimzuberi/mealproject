import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  Programme,
  ProgrammeProjectListQuery,
  ProjectTeamMember,
} from '../models/programme-project.model';
import { toHttpParams, unwrapList, unwrapPaginated } from '../utils/api.util';

export type ProgrammePayload = Partial<
  Pick<
    Programme,
    | 'code'
    | 'name'
    | 'description'
    | 'status'
    | 'objectives'
    | 'strategic_alignment'
    | 'sector_id'
    | 'department_id'
    | 'programme_manager_id'
    | 'strategic_plan_id'
    | 'starts_on'
    | 'ends_on'
    | 'budget_envelope'
    | 'currency'
    | 'donor_name'
    | 'donor_information'
    | 'partners'
    | 'target_groups'
    | 'thematic_area'
  >
>;

export type WorkflowCommentPayload = {
  comment?: string | null;
};

@Injectable({ providedIn: 'root' })
export class ProgrammeService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/programmes`;

  list(query?: ProgrammeProjectListQuery): Observable<Paginated<Programme>> {
    return this.http
      .get<ApiResponse<Programme[] | Paginated<Programme>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<Programme> {
    return this.http
      .get<ApiResponse<Programme>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: ProgrammePayload): Observable<Programme> {
    return this.http
      .post<ApiResponse<Programme>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: ProgrammePayload): Observable<Programme> {
    return this.http
      .put<ApiResponse<Programme>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }

  /** Optional endpoint — documents may land with programme enhancements. */
  uploadDocument(id: number, file: File, meta?: Record<string, string>): Observable<Record<string, unknown>> {
    const formData = new FormData();
    formData.append('file', file);
    if (meta) {
      for (const [key, value] of Object.entries(meta)) {
        formData.append(key, value);
      }
    }
    return this.http
      .post<ApiResponse<Record<string, unknown>>>(`${this.base}/${id}/documents`, formData)
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — team sync may land with programme enhancements. */
  syncTeam(id: number, members: Array<Pick<ProjectTeamMember, 'user_id' | 'role_label'>>): Observable<Programme> {
    return this.http
      .put<ApiResponse<Programme>>(`${this.base}/${id}/team`, { members })
      .pipe(map((res) => res.data));
  }

  /** Optional endpoint — geo sync may land with programme enhancements. */
  syncGeographicUnits(id: number, geographicUnitIds: number[]): Observable<Programme> {
    return this.http
      .put<ApiResponse<Programme>>(`${this.base}/${id}/geographic-units`, {
        geographic_unit_ids: geographicUnitIds,
      })
      .pipe(map((res) => res.data));
  }

  /** Optional documents list. */
  documents(id: number): Observable<Array<Record<string, unknown>>> {
    return this.http
      .get<ApiResponse<Array<Record<string, unknown>> | Paginated<Record<string, unknown>>>>(
        `${this.base}/${id}/documents`,
      )
      .pipe(map((res) => unwrapList(res.data)));
  }
}
