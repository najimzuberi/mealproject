# Programme / Project / RF Regression Report

- Full Laravel suite: **97 passed**
- Angular `ng build --configuration=production`: **success**
- Phase 1 org/auth/RBAC and Strategy modules remain green
- Monitoring Plan / Indicator chain still linked via CrossModulePhase2Test

## Known limitations
- Dedicated `programmes.*` permissions not introduced (still uses `projects.*`)
- Stakeholder CRUD is nested on create/update (no standalone resource API)
- RF hierarchy UI renders up to 4 nesting levels explicitly
- No separate Angular unit test suite for these pages
