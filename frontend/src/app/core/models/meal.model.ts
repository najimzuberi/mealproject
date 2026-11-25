/** MEAL domain models: indicators, monitoring plans, data collection, DQA. */

export type MealWorkflowStatus =
  | 'draft'
  | 'under_review'
  | 'returned'
  | 'approved'
  | 'active'
  | 'superseded'
  | 'retired'
  | 'archived'
  | string;

export type IndicatorResultLevel = 'impact' | 'outcome' | 'output' | 'process' | 'activity' | string;

export type IndicatorType = 'organizational' | 'donor' | 'government' | 'project' | string;

export type DirectionOfChange = 'increase' | 'decrease' | 'maintain' | string;

export type SubmissionStatus =
  | 'draft'
  | 'submitted'
  | 'returned'
  | 'verified'
  | 'validated'
  | 'approved'
  | 'published'
  | 'locked'
  | string;

export type AssignmentStatus = 'pending' | 'in_progress' | 'completed' | 'overdue' | string;

export type DqaAssessmentStatus = 'draft' | 'in_progress' | 'completed' | 'closed' | string;

export type DqaIssueStatus = 'open' | 'in_progress' | 'resolved' | 'closed' | string;

export interface MealListQuery {
  q?: string;
  search?: string;
  status?: string;
  project_id?: number;
  result_level?: string;
  monitoring_plan_id?: number;
  reporting_period_id?: number;
  assigned_to?: number;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: string;
  [key: string]: string | number | boolean | null | undefined;
}

export interface IndicatorBaseline {
  id?: number;
  value: string;
  baseline_date?: string | null;
  [key: string]: unknown;
}

export interface IndicatorTarget {
  id?: number;
  value: string;
  is_cumulative?: boolean;
  [key: string]: unknown;
}

export interface IndicatorDisaggregation {
  id?: number;
  dimension: 'sex' | 'age' | 'disability' | 'geography' | 'custom' | string;
  label?: string | null;
  is_required?: boolean;
  [key: string]: unknown;
}

export interface MealApprovalHistoryEntry {
  id?: number;
  action?: string;
  status?: string;
  comment?: string | null;
  user_id?: number | null;
  user_name?: string | null;
  created_at?: string | null;
  [key: string]: unknown;
}

export interface Indicator {
  id: number;
  ulid?: string;
  organization_id?: number;
  code: string;
  name: string;
  description?: string | null;
  definition?: string | null;
  measurement_unit?: string | null;
  numerator?: string | null;
  denominator?: string | null;
  formula?: string | null;
  aggregation_method?: string | null;
  direction_of_change?: DirectionOfChange | null;
  result_level?: IndicatorResultLevel | null;
  strategic_objective_id?: number | null;
  project_id?: number | null;
  results_framework_id?: number | null;
  result_id?: number | null;
  indicator_type?: IndicatorType | null;
  status: MealWorkflowStatus;
  version?: number | string | null;
  parent_indicator_id?: number | null;
  source_url?: string | null;
  source_organization?: string | null;
  source_accessed_on?: string | null;
  adaptation_notes?: string | null;
  data_source?: string | null;
  collection_method?: string | null;
  collection_frequency?: string | null;
  responsible_collector_id?: number | null;
  supervisor_id?: number | null;
  me_validator_id?: number | null;
  means_of_verification?: string | null;
  validation_rules?: string | null;
  limitations?: string | null;
  assumptions?: string | null;
  quality_checks?: string | null;
  approved_at?: string | null;
  approved_by?: number | null;
  created_by?: number | null;
  baselines?: IndicatorBaseline[];
  targets?: IndicatorTarget[];
  disaggregations?: IndicatorDisaggregation[];
  approval_histories?: MealApprovalHistoryEntry[];
  approval_history?: MealApprovalHistoryEntry[];
  versions?: Indicator[];
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export interface MonitoringPlanItem {
  id?: number;
  indicator_id: number;
  indicator?: Pick<Indicator, 'id' | 'code' | 'name' | 'version'> | null;
  baseline_value?: string | null;
  target_value?: string | null;
  data_source?: string | null;
  collection_method?: string | null;
  collection_frequency?: string | null;
  collection_deadline?: string | null;
  responsible_collector_id?: number | null;
  supervisor_id?: number | null;
  me_validator_id?: number | null;
  means_of_verification?: string | null;
  validation_rules?: string | null;
  reporting_format?: string | null;
  intended_use?: string | null;
  assumptions?: string | null;
  limitations?: string | null;
  sort_order?: number | null;
  [key: string]: unknown;
}

export interface MonitoringPlan {
  id: number;
  ulid?: string;
  organization_id?: number;
  code: string;
  project_id: number;
  title: string;
  description?: string | null;
  status: MealWorkflowStatus;
  version?: number | string | null;
  parent_monitoring_plan_id?: number | null;
  intended_use?: string | null;
  assumptions?: string | null;
  limitations?: string | null;
  approved_at?: string | null;
  approved_by?: number | null;
  created_by?: number | null;
  project?: { id: number; code?: string; name?: string; status?: string } | null;
  items?: MonitoringPlanItem[];
  approval_histories?: MealApprovalHistoryEntry[];
  approval_history?: MealApprovalHistoryEntry[];
  versions?: MonitoringPlan[];
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export interface ReportingAssignment {
  id: number;
  ulid?: string;
  organization_id?: number;
  monitoring_plan_id: number;
  monitoring_plan_item_id: number;
  reporting_period_id?: number | null;
  assigned_to: number;
  supervisor_id?: number | null;
  due_on?: string | null;
  status: AssignmentStatus;
  monitoring_plan?: Pick<MonitoringPlan, 'id' | 'code' | 'title' | 'status'> | null;
  indicator?: Pick<Indicator, 'id' | 'code' | 'name'> | null;
  assigned_user_name?: string | null;
  reporting_period_name?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export interface SubmissionValue {
  id?: number;
  dimension?: string | null;
  label?: string | null;
  value?: number | string | null;
  [key: string]: unknown;
}

export interface Submission {
  id: number;
  ulid?: string;
  organization_id?: number;
  reporting_assignment_id?: number | null;
  monitoring_plan_id: number;
  monitoring_plan_item_id: number;
  indicator_id: number;
  reporting_period_id?: number | null;
  submitted_by?: number | null;
  status: SubmissionStatus;
  numerator?: number | string | null;
  denominator?: number | string | null;
  value?: number | string | null;
  disaggregations?: Record<string, unknown> | null;
  notes?: string | null;
  submitted_at?: string | null;
  verified_at?: string | null;
  verified_by?: number | null;
  validated_at?: string | null;
  validated_by?: number | null;
  approved_at?: string | null;
  approved_by?: number | null;
  published_at?: string | null;
  locked_at?: string | null;
  indicator?: Pick<Indicator, 'id' | 'code' | 'name' | 'measurement_unit'> | null;
  monitoring_plan?: Pick<MonitoringPlan, 'id' | 'code' | 'title'> | null;
  values?: SubmissionValue[];
  approval_histories?: MealApprovalHistoryEntry[];
  approval_history?: MealApprovalHistoryEntry[];
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export interface DqaAssessment {
  id: number;
  ulid?: string;
  organization_id?: number;
  submission_id: number;
  assessed_by?: number | null;
  status: DqaAssessmentStatus;
  validity_score?: number | null;
  reliability_score?: number | null;
  completeness_score?: number | null;
  timeliness_score?: number | null;
  precision_score?: number | null;
  integrity_score?: number | null;
  overall_score?: number | string | null;
  summary?: string | null;
  completed_at?: string | null;
  submission?: Submission | null;
  issues?: DqaIssue[];
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export interface DqaIssue {
  id: number;
  ulid?: string;
  organization_id?: number;
  dqa_assessment_id: number;
  submission_id: number;
  assigned_to?: number | null;
  dimension?: string | null;
  severity?: string | null;
  title: string;
  description?: string | null;
  status: DqaIssueStatus;
  resolution_notes?: string | null;
  resolved_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export const INDICATOR_STATUSES: MealWorkflowStatus[] = [
  'draft',
  'under_review',
  'returned',
  'approved',
  'superseded',
  'retired',
];

export const MONITORING_PLAN_STATUSES: MealWorkflowStatus[] = [
  'draft',
  'under_review',
  'returned',
  'approved',
  'active',
  'superseded',
  'archived',
];

export const SUBMISSION_STATUSES: SubmissionStatus[] = [
  'draft',
  'submitted',
  'returned',
  'verified',
  'validated',
  'approved',
  'published',
  'locked',
];

export const ASSIGNMENT_STATUSES: AssignmentStatus[] = [
  'pending',
  'in_progress',
  'completed',
  'overdue',
];

export const DQA_ASSESSMENT_STATUSES: DqaAssessmentStatus[] = [
  'draft',
  'in_progress',
  'completed',
  'closed',
];

export const RESULT_LEVELS: IndicatorResultLevel[] = [
  'impact',
  'outcome',
  'output',
  'process',
  'activity',
];

export const INDICATOR_TYPES: IndicatorType[] = [
  'organizational',
  'donor',
  'government',
  'project',
];

export const DIRECTIONS_OF_CHANGE: DirectionOfChange[] = ['increase', 'decrease', 'maintain'];

export function statusLabel(status: string | null | undefined): string {
  if (!status) {
    return 'Unknown';
  }
  return status.replace(/_/g, ' ');
}

export function approvalEntries(
  entity:
    | { approval_history?: MealApprovalHistoryEntry[]; approval_histories?: MealApprovalHistoryEntry[] }
    | null
    | undefined,
): MealApprovalHistoryEntry[] {
  if (!entity) {
    return [];
  }
  if (Array.isArray(entity.approval_history) && entity.approval_history.length) {
    return entity.approval_history;
  }
  if (Array.isArray(entity.approval_histories)) {
    return entity.approval_histories;
  }
  return [];
}
