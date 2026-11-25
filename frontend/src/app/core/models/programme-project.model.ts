/** Programme / Project / Results Framework domain models. */

export type ProgrammeProjectStatus =
  | 'draft'
  | 'under_review'
  | 'returned'
  | 'approved'
  | 'active'
  | 'on_hold'
  | 'completed'
  | 'archived'
  | 'superseded'
  | string;

export type ResultLevel = 'impact' | 'outcome' | 'output' | 'process' | 'activity' | string;

export interface ProgrammeProjectListQuery {
  search?: string;
  status?: string;
  programme_id?: number;
  project_id?: number;
  strategic_plan_id?: number;
  page?: number;
  per_page?: number;
  [key: string]: string | number | boolean | null | undefined;
}

export interface ProjectTeamMember {
  user_id: number;
  name?: string | null;
  role_label?: string | null;
  email?: string | null;
}

export interface ProjectMilestone {
  id?: number;
  project_id?: number;
  project_activity_id?: number | null;
  title: string;
  due_on?: string | null;
  status?: string | null;
}

export interface ProjectActivity {
  id?: number;
  project_id?: number;
  title: string;
  description?: string | null;
  responsible_user_id?: number | null;
  starts_on?: string | null;
  ends_on?: string | null;
  status?: string | null;
  milestones?: ProjectMilestone[];
}

export interface ProjectRisk {
  id?: number;
  project_id?: number;
  description: string;
  likelihood?: string | null;
  impact?: string | null;
  mitigation?: string | null;
  status?: string | null;
}

export interface ProjectAssumption {
  id?: number;
  project_id?: number;
  description: string;
}

export interface Stakeholder {
  id?: number;
  ulid?: string;
  organization_id?: number;
  stakeholderable_type?: string;
  stakeholderable_id?: number;
  name: string;
  type?: string | null;
  role?: string | null;
  contact_email?: string | null;
  contact_phone?: string | null;
  notes?: string | null;
}

export interface Programme {
  id: number;
  ulid?: string;
  organization_id?: number;
  code: string;
  name: string;
  description?: string | null;
  objectives?: string | null;
  strategic_alignment?: string | null;
  sector_id?: number | null;
  department_id?: number | null;
  programme_manager_id?: number | null;
  strategic_plan_id?: number | null;
  starts_on?: string | null;
  ends_on?: string | null;
  budget_envelope?: number | string | null;
  currency?: string | null;
  donor_name?: string | null;
  donor_information?: string | null;
  partners?: string[] | null;
  target_groups?: string[] | null;
  thematic_area?: string | null;
  status: ProgrammeProjectStatus;
  created_by?: number | null;
  team_members?: ProjectTeamMember[];
  geographic_unit_ids?: number[];
  stakeholders?: Stakeholder[];
  documents?: Array<Record<string, unknown>>;
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export interface Project {
  id: number;
  ulid?: string;
  organization_id?: number;
  programme_id?: number | null;
  code: string;
  name: string;
  description?: string | null;
  background?: string | null;
  problem_statement?: string | null;
  target_groups?: string[] | null;
  strategic_plan_id?: number | null;
  strategic_objective_id?: number | null;
  annual_work_plan_id?: number | null;
  sector_id?: number | null;
  department_id?: number | null;
  project_manager_id?: number | null;
  funding_source?: string | null;
  donor_name?: string | null;
  donor_information?: string | null;
  budget?: number | string | null;
  currency?: string | null;
  starts_on: string;
  ends_on: string;
  status: ProgrammeProjectStatus;
  submitted_at?: string | null;
  approved_at?: string | null;
  approved_by?: number | null;
  created_by?: number | null;
  programme?: Pick<Programme, 'id' | 'code' | 'name' | 'status'> | null;
  activities?: ProjectActivity[];
  milestones?: ProjectMilestone[];
  assumptions?: ProjectAssumption[];
  risks?: ProjectRisk[];
  team_members?: ProjectTeamMember[];
  geographic_unit_ids?: number[];
  stakeholders?: Stakeholder[];
  results_framework_id?: number | null;
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export interface Result {
  id: number;
  ulid?: string;
  organization_id?: number;
  results_framework_id?: number;
  parent_id?: number | null;
  level: ResultLevel;
  code?: string | null;
  title: string;
  description?: string | null;
  sort_order?: number | null;
  department_id?: number | null;
  children?: Result[];
  assumptions?: Array<{ id?: number; description?: string }>;
  risks?: Array<{ id?: number; description?: string; likelihood?: string; impact?: string }>;
  indicators?: Array<{ id?: number; code?: string; title?: string; name?: string }>;
  [key: string]: unknown;
}

export interface ResultsFramework {
  id: number;
  ulid?: string;
  organization_id?: number;
  project_id: number;
  title: string;
  theory_of_change?: string | null;
  status: ProgrammeProjectStatus;
  version?: number | string | null;
  parent_framework_id?: number | null;
  submitted_at?: string | null;
  approved_at?: string | null;
  approved_by?: number | null;
  created_by?: number | null;
  results?: Result[];
  project?: Pick<Project, 'id' | 'code' | 'name' | 'status'> | null;
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export const PROGRAMME_STATUSES: ProgrammeProjectStatus[] = [
  'draft',
  'active',
  'archived',
];

export const PROJECT_STATUSES: ProgrammeProjectStatus[] = [
  'draft',
  'under_review',
  'returned',
  'approved',
  'active',
  'on_hold',
  'completed',
  'archived',
];

export const RESULTS_FRAMEWORK_STATUSES: ProgrammeProjectStatus[] = [
  'draft',
  'under_review',
  'returned',
  'approved',
  'active',
  'superseded',
  'archived',
];

export const RESULT_LEVELS: ResultLevel[] = [
  'impact',
  'outcome',
  'output',
  'activity',
];

export function statusLabel(status: string | null | undefined): string {
  if (!status) {
    return 'Unknown';
  }
  return status.replace(/_/g, ' ');
}

export function resultLevelLabel(level: string | null | undefined): string {
  if (!level) {
    return 'Unknown';
  }
  return level.charAt(0).toUpperCase() + level.slice(1);
}

export function formatBudget(
  amount: number | string | null | undefined,
  currency?: string | null,
): string {
  if (amount === null || amount === undefined || amount === '') {
    return '—';
  }
  const value = Number(amount);
  if (Number.isNaN(value)) {
    return String(amount);
  }
  const formatted = value.toLocaleString(undefined, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
  return currency ? `${currency} ${formatted}` : formatted;
}

/** Flatten nested result tree into a parent-selectable list. */
export function flattenResults(results: Result[] | null | undefined, depth = 0): Array<Result & { depth: number }> {
  if (!results?.length) {
    return [];
  }
  const rows: Array<Result & { depth: number }> = [];
  for (const result of results) {
    rows.push({ ...result, depth });
    if (result.children?.length) {
      rows.push(...flattenResults(result.children, depth + 1));
    }
  }
  return rows;
}
