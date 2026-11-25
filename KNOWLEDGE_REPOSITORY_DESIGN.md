# Knowledge Repository — Design

## Entity
`knowledge_documents` with metadata, tags/keywords, access level, versioning via `parent_document_id`, optional file on `local` disk.

## Security
- MIME allow-list + 10MB limit
- Org-scoped policies (`knowledge.view|download|manage`)
- Download audited
- AI fields (`ai_summary`, `ai_assisted`) stored as drafts only — never bypass permissions
