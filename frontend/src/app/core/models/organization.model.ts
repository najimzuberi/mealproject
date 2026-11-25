export interface OrganizationProfile {
  id: number;
  ulid: string;
  name: string;
  code: string;
  registration_number: string | null;
  type: string;
  description: string | null;
  email: string | null;
  phone: string | null;
  website: string | null;
  logo_path: string | null;
  logo_url?: string | null;
  country: string | null;
  headquarters_region?: string | null;
  physical_address?: string | null;
  postal_address?: string | null;
  address: string | null;
  timezone?: string | null;
  default_language?: string | null;
  default_currency?: string | null;
  status: string;
  sectors?: Sector[];
  settings?: OrganizationSetting[];
  created_at?: string | null;
  updated_at?: string | null;
}

export interface OrganizationSetting {
  key: string;
  value: unknown;
}

export interface Department {
  id: number;
  ulid: string;
  organization_id: number;
  parent_id?: number | null;
  head_user_id?: number | null;
  name: string;
  code: string | null;
  description: string | null;
  email?: string | null;
  phone?: string | null;
  status: string;
  created_at?: string | null;
}

export interface Office {
  id: number;
  ulid: string;
  organization_id: number;
  department_id: number | null;
  parent_id?: number | null;
  geographic_unit_id?: number | null;
  manager_user_id?: number | null;
  name: string;
  code: string | null;
  type?: string | null;
  description?: string | null;
  address: string | null;
  country?: string | null;
  region?: string | null;
  district?: string | null;
  contact_person?: string | null;
  email?: string | null;
  phone?: string | null;
  status: string;
}

export interface FacilityType {
  id: number;
  ulid: string;
  organization_id: number;
  name: string;
  code: string;
  description?: string | null;
  status: string;
}

export interface Facility {
  id: number;
  ulid: string;
  organization_id: number;
  office_id: number | null;
  facility_type_id?: number | null;
  geographic_unit_id?: number | null;
  name: string;
  code: string | null;
  type: string | null;
  description?: string | null;
  registration_number?: string | null;
  address: string | null;
  latitude?: number | null;
  longitude?: number | null;
  contact_person?: string | null;
  contact_phone?: string | null;
  status: string;
}

export interface GeographicType {
  id: number;
  ulid: string;
  organization_id: number;
  name: string;
  code: string;
  default_level?: number;
  description?: string | null;
  status: string;
}

export interface GeographicUnit {
  id: number;
  ulid: string;
  organization_id: number;
  parent_id: number | null;
  geographic_type_id?: number | null;
  name: string;
  code: string | null;
  level: number;
  type: string;
  description?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  status: string;
  children?: GeographicUnit[];
}

export interface Sector {
  id: number;
  name: string;
  code: string;
  description: string | null;
  is_system?: boolean;
  status: string;
}

export interface OrgUser {
  id: number;
  ulid: string;
  name: string;
  email: string;
  phone: string | null;
  status: string;
  last_login_at?: string | null;
  roles?: Array<{ id: number; name: string; display_name?: string }>;
  organizations?: Array<{ id: number; name: string; code: string }>;
}

export interface Team {
  id: number;
  ulid: string;
  organization_id: number;
  department_id?: number | null;
  office_id?: number | null;
  leader_user_id?: number | null;
  name: string;
  code?: string | null;
  description?: string | null;
  status: string;
  members?: OrgUser[];
}

export interface ReportingCalendar {
  id: number;
  ulid: string;
  organization_id: number;
  name: string;
  frequency: string;
  description: string | null;
  allow_overlapping_periods: boolean;
  status: string;
  periods?: ReportingPeriod[];
}

export interface ReportingPeriod {
  id: number;
  ulid: string;
  reporting_calendar_id: number;
  organization_id: number;
  name: string;
  starts_on: string | null;
  ends_on: string | null;
  status: string;
}

export interface ListQuery {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
}
