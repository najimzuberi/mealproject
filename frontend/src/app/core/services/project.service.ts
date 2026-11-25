import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import {
  ProgrammeProjectListQuery,
  Project,
  ProjectActivity,
  ProjectAssumption,
  ProjectRisk,
  ProjectTeamMember,
} from '../models/programme-project.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type ProjectPayload = Partial<
  Pick<
    Project,
    | 'programme_id'
    | 'code'
    | 'name'
    | 'description'
    | 'background'
    | 'problem_statement'
    | 'target_groups'
    | 'strategic_plan_id'
    | 'strategic_objective_id'
    | 'annual_work_plan_id'
    | 'sector_id'
    | 'department_id'
    | 'project_manager_id'
    | 'funding_source'
    | 'donor_name'
    | 'donor_information'
    | 'budget'
    | 'currency'
    | 'starts_on'
    | 'ends_on'
    | 'status'
  >
> & {
  activities?: ProjectActivity[];
  assumptions?: ProjectAssumption[];
  risks?: ProjectRisk[];
};

export type WorkflowCommentPayload = {
  comment?: string | null;
};

@Injectable({ providedIn: 'root' })
export class ProjectService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/projects`;

  list(query?: ProgrammeProjectListQuery): Observable<Paginated<Project>> {
    return this.http
      .get<ApiResponse<Project[] | Paginated<Project>>>(this.base, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<Project> {
    return this.http
      .get<ApiResponse<Project>>(`${this.base}/${id}`)
      .pipe(map((res) => res.data));
  }

  create(payload: ProjectPayload): Observable<Project> {
    return this.http
      .post<ApiResponse<Project>>(this.base, payload)
      .pipe(map((res) => res.data));
  }

  update(id: number, payload: ProjectPayload): Observable<Project> {
    return this.http
      .put<ApiResponse<Project>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }

  activate(id: number): Observable<Project> {
    return this.http
      .post<ApiResponse<Project>>(`${this.base}/${id}/activate`, {})
      .pipe(map((res) => res.data));
  }

  archive(id: number): Observable<Project> {
    return this.http
      .post<ApiResponse<Project>>(`${this.base}/${id}/archive`, {})
      .pipe(map((res) => res.data));
  }

  /** Optional — may land with project workflow enhancements. */
  submit(id: number, payload?: WorkflowCommentPayload): Observable<Project> {
    return this.http
      .post<ApiResponse<Project>>(`${this.base}/${id}/submit`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional — may land with project workflow enhancements. */
  returnProject(id: number, payload?: WorkflowCommentPayload): Observable<Project> {
    return this.http
      .post<ApiResponse<Project>>(`${this.base}/${id}/return`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  /** Optional — may land with project workflow enhancements. */
  approve(id: number, payload?: WorkflowCommentPayload): Observable<Project> {
    return this.http
      .post<ApiResponse<Project>>(`${this.base}/${id}/approve`, payload ?? {})
      .pipe(map((res) => res.data));
  }

  syncTeam(id: number, members: Array<Pick<ProjectTeamMember, 'user_id' | 'role_label'>>): Observable<Project> {
    return this.http
      .put<ApiResponse<Project>>(`${this.base}/${id}/team`, { members })
      .pipe(map((res) => res.data));
  }

  syncGeographicUnits(id: number, geographicUnitIds: number[]): Observable<Project> {
    return this.http
      .put<ApiResponse<Project>>(`${this.base}/${id}/geographic-units`, {
        geographic_unit_ids: geographicUnitIds,
      })
      .pipe(map((res) => res.data));
  }
}
