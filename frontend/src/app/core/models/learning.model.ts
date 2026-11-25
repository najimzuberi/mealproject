/** Learning: evaluations, feedback & complaints, lessons learned, knowledge repository. */

export type EvaluationStatus =
  | 'draft'
  | 'under_review'
  | 'returned'
  | 'approved'
  | 'in_progress'
  | 'completed'
  | 'archived'
  | string;

export type FeedbackCaseStatus =
  | 'received'
  | 'assigned'
  | 'under_investigation'
  | 'escalated'
  | 'resolved'
  | 'closed'
  | 'referred'
  | string;

export type LessonStatus =
  | 'identified'
  | 'documented'
  | 'under_review'
  | 'returned'
  | 'approved'
  | 'action_assigned'
  | 'applied'
  | 'archived'
  | string;

export type KnowledgeDocumentStatus = 'draft' | 'published' | 'archived' | string;

export type LearningActionStatus =
  | 'open'
  | 'in_progress'
  | 'completed'
  | 'cancelled'
  | 'overdue'
  | string;

export type ConfidentialityLevel = 'public' | 'internal' | 'restricted' | 'safeguarding' | string;

export const EVALUATION_STATUSES = [
  'draft',
  'under_review',
  'returned',
  'approved',
  'in_progress',
  'completed',
  'archived',
] as const;

export const EVALUATION_TYPES = [
  'baseline',
  'midterm',
  'endline',
  'impact',
  'process',
  'thematic',
  'real_time',
] as const;

export const FEEDBACK_CASE_STATUSES = [
  'received',
  'assigned',
  'under_investigation',
  'escalated',
  'resolved',
  'closed',
  'referred',
] as const;

export const FEEDBACK_TYPES = ['feedback', 'complaint', 'suggestion', 'compliment', 'inquiry'] as const;

export const PRIORITIES = ['low', 'medium', 'high', 'critical'] as const;

export const CONFIDENTIALITY_LEVELS = ['public', 'internal', 'restricted', 'safeguarding'] as const;

export const LESSON_STATUSES = [
  'identified',
  'documented',
  'under_review',
  'returned',
  'approved',
  'action_assigned',
  'applied',
  'archived',
] as const;

export const KNOWLEDGE_DOCUMENT_STATUSES = ['draft', 'published', 'archived'] as const;

export const LEARNING_ACTION_STATUSES = [
  'open',
  'in_progress',
  'completed',
  'cancelled',
  'overdue',
] as const;

export interface LearningListQuery {
  q?: string;
  search?: string;
  status?: string;
  evaluation_type?: string;
  feedback_type?: string;
  category?: string;
  priority?: string;
  programme_id?: number;
  project_id?: number;
  feedback_category_id?: number;
  confidentiality_level?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: string;
  [key: string]: string | number | boolean | null | undefined;
}

export interface NamedRef {
  id: number;
  name: string;
  code?: string | null;
}

export interface UserRef {
  id: number;
  name: string;
  email?: string | null;
}

export interface EvaluationQuestion {
  id: number;
  evaluation_id: number;
  question: string;
  criterion?: string | null;
  sort_order?: number;
}

export interface EvaluationFinding {
  id: number;
  ulid?: string;
  evaluation_id: number;
  reference: string;
  title: string;
  description?: string | null;
  supporting_evidence?: string | null;
  project_id?: number | null;
  result_id?: number | null;
  indicator_id?: number | null;
  conclusion?: string | null;
  significance?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface EvaluationRecommendation {
  id: number;
  ulid?: string;
  evaluation_id: number;
  evaluation_finding_id?: number | null;
  title: string;
  description?: string | null;
  priority?: string;
  supporting_evidence?: string | null;
  responsible_department_id?: number | null;
  management_response_status?: string | null;
  management_response?: string | null;
  response_date?: string | null;
  responsible_manager_id?: number | null;
  finding?: EvaluationFinding | null;
  responsible_department?: NamedRef | null;
  responsible_manager?: UserRef | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface LearningAction {
  id: number;
  ulid?: string;
  actionable_type?: string | null;
  actionable_id?: number | null;
  description: string;
  responsible_person_id?: number | null;
  department_id?: number | null;
  due_on?: string | null;
  required_evidence?: string | null;
  status: LearningActionStatus;
  completed_on?: string | null;
  progress_notes?: string | null;
  responsible_person?: UserRef | null;
  department?: NamedRef | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface Evaluation {
  id: number;
  ulid?: string;
  organization_id?: number;
  code: string;
  title: string;
  evaluation_type?: string | null;
  programme_id?: number | null;
  project_id?: number | null;
  purpose?: string | null;
  scope?: string | null;
  starts_on?: string | null;
  ends_on?: string | null;
  evaluation_lead_id?: number | null;
  evaluation_team?: Array<number | string> | null;
  methodology?: string | null;
  data_collection_methods?: string | null;
  target_population?: string | null;
  sample_size?: number | null;
  geographic_coverage?: string | null;
  tools?: string | null;
  timeline_notes?: string | null;
  status: EvaluationStatus;
  approved_at?: string | null;
  completed_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  programme?: NamedRef | null;
  project?: NamedRef | null;
  evaluation_lead?: UserRef | null;
  questions?: EvaluationQuestion[];
  findings?: EvaluationFinding[];
  recommendations?: EvaluationRecommendation[];
  learning_actions?: LearningAction[];
}

export interface FeedbackCategory {
  id: number;
  ulid?: string;
  name: string;
  code?: string | null;
  is_sensitive?: boolean;
  is_active?: boolean;
}

/**
 * List summaries intentionally omit / redact safeguarding & restricted bodies
 * and complainant PII. Prefer these safe fields in list tables.
 */
export interface FeedbackCaseSummary {
  id: number;
  ulid?: string;
  reference_number: string;
  submitted_at?: string | null;
  feedback_type: string;
  feedback_category_id?: number | null;
  submission_channel?: string | null;
  project_id?: number | null;
  is_anonymous?: boolean;
  confidentiality_level?: ConfidentialityLevel;
  priority?: string;
  status: FeedbackCaseStatus;
  assigned_to?: number | null;
  resolution_deadline?: string | null;
  escalation_level?: number;
  created_at?: string | null;
  updated_at?: string | null;
  category?: FeedbackCategory | null;
  project?: NamedRef | null;
  assignee?: UserRef | null;
  /** May be redacted / null on list payloads. */
  description?: string | null;
  complainant_name?: string | null;
  complainant_contact?: string | null;
}

export interface FeedbackCase extends FeedbackCaseSummary {
  geographic_unit_id?: number | null;
  consent_given?: boolean;
  responsible_department_id?: number | null;
  supervisor_id?: number | null;
  escalation_reason?: string | null;
  investigation_notes?: string | null;
  resolution_description?: string | null;
  action_taken?: string | null;
  resolved_at?: string | null;
  closed_at?: string | null;
  closed_by?: number | null;
  feedback_loop_status?: string | null;
  responsible_department?: NamedRef | null;
  supervisor?: UserRef | null;
  geographic_unit?: NamedRef | null;
}

export interface Lesson {
  id: number;
  ulid?: string;
  organization_id?: number;
  reference: string;
  title: string;
  description?: string | null;
  source?: string | null;
  project_id?: number | null;
  programme_id?: number | null;
  evaluation_id?: number | null;
  feedback_case_id?: number | null;
  context?: string | null;
  challenge?: string | null;
  what_worked?: string | null;
  what_did_not_work?: string | null;
  recommendation?: string | null;
  responsible_person_id?: number | null;
  action_required?: string | null;
  status: LessonStatus;
  approved_at?: string | null;
  applied_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  programme?: NamedRef | null;
  project?: NamedRef | null;
  evaluation?: { id: number; title?: string; code?: string | null } | null;
  feedback_case?: { id: number; reference_number?: string } | null;
  responsible_person?: UserRef | null;
  learning_actions?: LearningAction[];
  links?: Array<{ id: number; linkable_type: string; linkable_id: number }>;
}

export interface KnowledgeDocument {
  id: number;
  ulid?: string;
  organization_id?: number;
  code: string;
  title: string;
  category?: string | null;
  description?: string | null;
  document_type?: string | null;
  tags?: string[] | null;
  keywords?: string[] | null;
  project_id?: number | null;
  programme_id?: number | null;
  department_id?: number | null;
  author_id?: number | null;
  published_on?: string | null;
  version?: number;
  access_level?: string;
  status: KnowledgeDocumentStatus;
  disk?: string | null;
  path?: string | null;
  original_name?: string | null;
  mime_type?: string | null;
  size?: number | null;
  parent_document_id?: number | null;
  ai_summary?: string | null;
  ai_assisted?: boolean;
  created_at?: string | null;
  updated_at?: string | null;
  programme?: NamedRef | null;
  project?: NamedRef | null;
  department?: NamedRef | null;
  author?: UserRef | null;
}

export function statusLabel(status: string | null | undefined): string {
  if (!status) {
    return '—';
  }
  return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function isSensitiveConfidentiality(level: string | null | undefined): boolean {
  return level === 'restricted' || level === 'safeguarding';
}
