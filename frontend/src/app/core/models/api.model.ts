export interface ApiResponse<T> {
  success: boolean;
  message: string | null;
  data: T;
  meta: Record<string, unknown> | null;
  errors: Record<string, string[]> | null;
}

export interface Paginated<T> {
  items: T[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface OrganizationSummary {
  id: number;
  ulid: string;
  name: string;
  code: string;
  status: string;
  is_default?: boolean;
}

export interface AuthUser {
  id: number;
  ulid: string;
  name: string;
  email: string;
  status?: string;
  organizations: OrganizationSummary[];
  active_organization_id: number | null;
  roles: string[];
  permissions: string[];
}

export interface LoginResult {
  token: string;
  token_type: string;
  user: { id: number; ulid: string; name: string; email: string };
  default_organization_id: number | null;
}

export interface DashboardOverview {
  organization_id: number;
  generated_at: string;
  kpis: {
    total_projects: number;
    active_projects: number;
    strategic_plans: number;
    active_indicators: number;
    monitoring_plans: number;
    pending_approvals: number;
  };
  project_status: Record<string, number>;
  reporting_periods: Array<{
    id: number;
    name: string;
    status: string;
    starts_on: string | null;
    ends_on: string | null;
    calendar: string | null;
  }>;
  pending_tasks: Array<{
    type: string;
    id: number;
    title: string;
    status: string;
    route: string;
  }>;
  recent_activities: Array<{
    id: number;
    user_id: number | null;
    action: string;
    module: string;
    created_at: string | null;
  }>;
  objectives_progress: { available: boolean; message: string; items: unknown[] };
  indicator_performance: { available: boolean; message: string; items: unknown[] };
}
