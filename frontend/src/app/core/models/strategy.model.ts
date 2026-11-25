/** Strategy / AWP domain models. Optional fields tolerate parallel backend enhancements. */

export type WorkflowStatus =
  | 'draft'
  | 'under_review'
  | 'returned'
  | 'approved'
  | 'active'
  | 'archived'
  | string;

export interface StrategyListQuery {
  search?: string;
  status?: string;
  strategic_plan_id?: number;
  planning_year?: number;
  page?: number;
  per_page?: number;
  [key: string]: string | number | boolean | null | undefined;
}

export interface StrategicTheme {
  id?: number;
  strategic_plan_id?: number;
  name: string;
  description?: string | null;
  sort_order?: number;
}

export interface KeyResultArea {
  id?: number;
  strategic_objective_id?: number;
  code?: string | null;
  title: string;
  description?: string | null;
  sort_order?: number;
}

/** Alias used in UI copy */
export type KRA = KeyResultArea;

export interface StrategicIndicator {
  id?: number;
  code?: string | null;
  title?: string;
  name?: string;
  description?: string | null;
  unit?: string | null;
  baseline?: number | string | null;
  target?: number | string | null;
  [key: string]: unknown;
}

export interface Outcome {
  id?: number;
  code?: string | null;
  title: string;
  description?: string | null;
  sort_order?: number;
  indicators?: StrategicIndicator[];
  [key: string]: unknown;
}

export interface StrategicObjective {
  id?: number;
  strategic_goal_id?: number;
  code?: string | null;
  title: string;
  description?: string | null;
  expected_result?: string | null;
  sort_order?: number;
  department_ids?: number[];
  responsible_user_ids?: number[];
  kra?: KeyResultArea[];
  key_result_areas?: KeyResultArea[];
  outcomes?: Outcome[];
  indicators?: StrategicIndicator[];
}

/** Alias used in UI copy */
export type Objective = StrategicObjective;

export interface StrategicGoal {
  id?: number;
  strategic_plan_id?: number;
  strategic_theme_id?: number | null;
  theme_index?: number | null;
  code?: string | null;
  title: string;
  description?: string | null;
  expected_result?: string | null;
  sort_order?: number;
  department_ids?: number[];
  objectives?: StrategicObjective[];
}

/** Alias used in UI copy */
export type Goal = StrategicGoal;

/** Plan-level priority (distinct from AWP priorities linked to objectives). */
export interface StrategicPlanPriority {
  id?: number;
  strategic_plan_id?: number;
  department_id?: number | null;
  code?: string | null;
  title: string;
  description?: string | null;
  status?: string | null;
  sort_order?: number;
}

export interface StrategyDocument {
  id?: number;
  name?: string;
  title?: string;
  file_name?: string;
  url?: string | null;
  path?: string | null;
  mime_type?: string | null;
  size?: number | null;
  uploaded_at?: string | null;
  created_at?: string | null;
  [key: string]: unknown;
}

/** Alias used in UI copy */
export type Document = StrategyDocument;

export interface ApprovalHistoryEntry {
  id?: number;
  action?: string;
  status?: string;
  comment?: string | null;
  user_id?: number | null;
  user_name?: string | null;
  created_at?: string | null;
  [key: string]: unknown;
}

export interface StrategicPlan {
  id: number;
  ulid?: string;
  organization_id?: number;
  code: string;
  title: string;
  description?: string | null;
  vision?: string | null;
  mission?: string | null;
  strategic_context?: string | null;
  core_values?: string[] | null;
  start_date: string;
  end_date: string;
  status: WorkflowStatus;
  version?: number | string | null;
  parent_plan_id?: number | null;
  owner_user_id?: number | null;
  department_id?: number | null;
  approved_at?: string | null;
  approved_by?: number | null;
  created_by?: number | null;
  themes?: StrategicTheme[];
  priorities?: StrategicPlanPriority[];
  goals?: StrategicGoal[];
  documents?: StrategyDocument[];
  approval_history?: ApprovalHistoryEntry[];
  versions?: StrategicPlan[];
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

export interface AwpMilestone {
  id?: number;
  awp_activity_id?: number;
  quarter: 'Q1' | 'Q2' | 'Q3' | 'Q4' | string;
  title: string;
  due_on?: string | null;
  status?: string;
}

/** Alias used in UI copy */
export type Milestone = AwpMilestone;

export interface BudgetLine {
  id?: number;
  activity_id?: number;
  title?: string;
  description?: string | null;
  amount?: number | null;
  currency?: string | null;
  [key: string]: unknown;
}

export interface AwpActivity {
  id?: number;
  awp_priority_id?: number;
  department_id?: number | null;
  responsible_user_id?: number | null;
  title: string;
  description?: string | null;
  deliverable?: string | null;
  budget_estimate?: number | string | null;
  starts_on?: string | null;
  ends_on?: string | null;
  status?: string;
  milestones?: AwpMilestone[];
  budget_lines?: BudgetLine[];
}

/** Alias used in UI copy */
export type Activity = AwpActivity;

export interface AwpPriority {
  id?: number;
  annual_work_plan_id?: number;
  strategic_objective_id: number;
  title: string;
  description?: string | null;
  sort_order?: number;
  activities?: AwpActivity[];
}

/** Alias used in UI copy */
export type Priority = AwpPriority;

export interface AnnualWorkPlan {
  id: number;
  ulid?: string;
  organization_id?: number;
  strategic_plan_id: number;
  code: string;
  title: string;
  planning_year: number;
  start_date?: string | null;
  end_date?: string | null;
  currency?: string | null;
  total_budget?: number | string | null;
  description?: string | null;
  status: WorkflowStatus;
  version?: number | string | null;
  parent_awp_id?: number | null;
  owner_user_id?: number | null;
  department_id?: number | null;
  submitted_at?: string | null;
  approved_at?: string | null;
  approved_by?: number | null;
  created_by?: number | null;
  priorities?: AwpPriority[];
  plan_milestones?: AwpMilestone[];
  budget_lines?: BudgetLine[];
  budget_summary?: {
    total?: number;
    currency?: string | null;
    [key: string]: unknown;
  };
  approval_history?: ApprovalHistoryEntry[];
  documents?: StrategyDocument[];
  strategic_plan?: Pick<StrategicPlan, 'id' | 'title' | 'code' | 'status'> | null;
  created_at?: string | null;
  updated_at?: string | null;
  [key: string]: unknown;
}

/** Alias used in UI copy */
export type AWP = AnnualWorkPlan;

export const STRATEGY_STATUSES: WorkflowStatus[] = [
  'draft',
  'under_review',
  'returned',
  'approved',
  'active',
  'archived',
];

export function statusLabel(status: string | null | undefined): string {
  if (!status) {
    return 'Unknown';
  }
  return status.replace(/_/g, ' ');
}

export function sumBudgetEstimate(awp: AnnualWorkPlan | null | undefined): number {
  const recorded = Number(awp?.total_budget ?? awp?.budget_summary?.total ?? NaN);
  if (!Number.isNaN(recorded) && recorded > 0) {
    return recorded;
  }
  if (!awp?.priorities?.length) {
    return Number(awp?.budget_summary?.total ?? 0) || 0;
  }
  let total = 0;
  for (const priority of awp.priorities) {
    for (const activity of priority.activities ?? []) {
      const value = Number(activity.budget_estimate ?? 0);
      if (!Number.isNaN(value)) {
        total += value;
      }
    }
  }
  return total;
}

export function flattenMilestones(awp: AnnualWorkPlan | null | undefined): Array<
  AwpMilestone & { activity_title?: string; priority_title?: string }
> {
  if (!awp?.priorities?.length) {
    return [];
  }
  const rows: Array<AwpMilestone & { activity_title?: string; priority_title?: string }> = [];
  for (const priority of awp.priorities) {
    for (const activity of priority.activities ?? []) {
      for (const milestone of activity.milestones ?? []) {
        rows.push({
          ...milestone,
          activity_title: activity.title,
          priority_title: priority.title,
        });
      }
    }
  }
  return rows;
}

export function flattenActivities(awp: AnnualWorkPlan | null | undefined): Array<
  AwpActivity & { priority_title?: string }
> {
  if (!awp?.priorities?.length) {
    return [];
  }
  const rows: Array<AwpActivity & { priority_title?: string }> = [];
  for (const priority of awp.priorities) {
    for (const activity of priority.activities ?? []) {
      rows.push({ ...activity, priority_title: priority.title });
    }
  }
  return rows;
}
