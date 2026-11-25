import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse, Paginated } from '../models/api.model';
import { Facility, FacilityType, ListQuery } from '../models/organization.model';
import { toHttpParams, unwrapPaginated } from '../utils/api.util';

export type FacilityPayload = Partial<
  Pick<
    Facility,
    | 'office_id'
    | 'facility_type_id'
    | 'geographic_unit_id'
    | 'name'
    | 'code'
    | 'type'
    | 'description'
    | 'registration_number'
    | 'address'
    | 'latitude'
    | 'longitude'
    | 'contact_person'
    | 'contact_phone'
    | 'status'
  >
>;

export type FacilityTypePayload = Partial<Pick<FacilityType, 'name' | 'code' | 'description' | 'status'>>;

@Injectable({ providedIn: 'root' })
export class FacilityService {
  private readonly http = inject(HttpClient);
  private readonly base = `${environment.apiBaseUrl}/facilities`;
  private readonly typesBase = `${environment.apiBaseUrl}/facility-types`;

  list(query?: ListQuery): Observable<Paginated<Facility>> {
    return this.http
      .get<ApiResponse<Facility[] | Paginated<Facility>>>(this.base, { params: toHttpParams(query) })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  get(id: number): Observable<Facility> {
    return this.http.get<ApiResponse<Facility>>(`${this.base}/${id}`).pipe(map((res) => res.data));
  }

  create(payload: FacilityPayload): Observable<Facility> {
    return this.http.post<ApiResponse<Facility>>(this.base, payload).pipe(map((res) => res.data));
  }

  update(id: number, payload: FacilityPayload): Observable<Facility> {
    return this.http
      .put<ApiResponse<Facility>>(`${this.base}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  delete(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.base}/${id}`).pipe(map(() => undefined));
  }

  listTypes(query?: ListQuery): Observable<Paginated<FacilityType>> {
    return this.http
      .get<ApiResponse<FacilityType[] | Paginated<FacilityType>>>(this.typesBase, {
        params: toHttpParams(query),
      })
      .pipe(map((res) => unwrapPaginated(res.data)));
  }

  createType(payload: FacilityTypePayload): Observable<FacilityType> {
    return this.http
      .post<ApiResponse<FacilityType>>(this.typesBase, payload)
      .pipe(map((res) => res.data));
  }

  updateType(id: number, payload: FacilityTypePayload): Observable<FacilityType> {
    return this.http
      .put<ApiResponse<FacilityType>>(`${this.typesBase}/${id}`, payload)
      .pipe(map((res) => res.data));
  }

  deleteType(id: number): Observable<void> {
    return this.http.delete<ApiResponse<null>>(`${this.typesBase}/${id}`).pipe(map(() => undefined));
  }
}
