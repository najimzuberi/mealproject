import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { GeographicType, GeographicUnit, ListQuery } from '../models/organization.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type GeographicUnitPayload = Partial<
  Pick<
    GeographicUnit,
    | 'parent_id'
    | 'geographic_type_id'
    | 'name'
    | 'code'
    | 'type'
    | 'description'
    | 'latitude'
    | 'longitude'
    | 'status'
  >
>;

export type GeographicTypePayload = Partial<
  Pick<GeographicType, 'name' | 'code' | 'default_level' | 'description' | 'status'>
>;

@Injectable({ providedIn: 'root' })
export class GeographyService {
  private readonly http = inject(HttpClient);
  private readonly unitsBase = `${environment.apiBaseUrl}/geographic-units`;
  private readonly typesBase = `${environment.apiBaseUrl}/geographic-types`;

  listUnits(query?: ListQuery): Observable<Paginated<GeographicUnit>> {
    return this.http
      .get<ApiResponse<GeographicUnit[] | Paginated<GeographicUnit>>>(this.unitsBase, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  getUnit(id: number): Observable<GeographicUnit> {
    return this.http
      .get<ApiResponse<GeographicUnit>>(`${this.unitsBase}/${id}`)
      .pipe(map((res) => res.data));
  }

  createUnit(payload: GeographicUnitPayload): Observable<GeographicUnit> {
    return this.http
      .post<ApiResponse<GeographicUnit>>(this.unitsBase, payload)
      .pipe(map((res) => res.data));
  }

  updateUnit(id: number, payload: GeographicUnitPayload): Observable<GeographicUnit> {
    return this.http
      .put<ApiResponse<GeographicUnit>>(`${this.unitsBase}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  deleteUnit(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.unitsBase}/${id}`).pipe(map(() => undefined));
  }

  listTypes(query?: ListQuery): Observable<Paginated<GeographicType>> {
    return this.http
      .get<ApiResponse<GeographicType[] | Paginated<GeographicType>>>(this.typesBase, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  createType(payload: GeographicTypePayload): Observable<GeographicType> {
    return this.http
      .post<ApiResponse<GeographicType>>(this.typesBase, payload)
      .pipe(map((res) => res.data));
  }

  updateType(id: number, payload: GeographicTypePayload): Observable<GeographicType> {
    return this.http
      .put<ApiResponse<GeographicType>>(`${this.typesBase}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  deleteType(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.typesBase}/${id}`).pipe(map(() => undefined));
  }
}
