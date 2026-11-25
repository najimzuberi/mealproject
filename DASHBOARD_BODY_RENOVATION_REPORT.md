# MEAL MASTER – Dashboard Body Renovation Report

## Root cause of the original layout issue

KPI cards were implemented as a nested structure:

```html
<button mat-button class="stat-card">
  <mat-card class="stat-card__surface">…</mat-card>
</button>
```

Angular Material `mat-button` applies `inline-flex`, constrained line-height, overflow clipping and absolute ripple layers. Nesting a full outlined `mat-card` inside that button collapsed the card’s natural block height. In a CSS Grid, multiple cards then visually stacked / overlapped instead of occupying independent grid cells.

Contributing factors:

- Host `app-stat-card` lacked reliable `display: block`, `min-width: 0` and `height: 100%` for grid participation.
- Card layout depended on Material button chrome rather than a dedicated card surface.

**Fix:** remove the `mat-button` wrapper. Use `mat-card` as the root interactive surface with keyboard activation, and give every host / grid item `minmax(0, 1fr)` grid tracks with no absolute positioning or negative margins.

## Components modified

| Component | Change |
|---|---|
| `stat-card` | Rebuilt as vertical enterprise KPI card; no `mat-button` wrapper |
| `dashboard-overview` | Welcome / KPI / analytics / ops sections; responsive grids |
| `project-status-widget` | Shared widget chrome, chart container, empty copy |
| `objectives-progress-widget` | Shared widget chrome + unavailable empty state |
| `indicator-performance-widget` | Shared widget chrome + unavailable empty state |
| `reporting-calendar-widget` | Compact period list + empty state |
| `pending-tasks-widget` | Renamed UI to Pending Approvals; compact list + View action |
| `recent-activities-widget` | Avatar list instead of dense table |
| `empty-state` | Centered professional empty treatment |
| `main-layout` (content padding only) | Slightly larger content padding / `min-width: 0` |
| `breadcrumb` | Null-safe route walk (console error fix) |

## Components / files created

| File | Purpose |
|---|---|
| `features/dashboard/_dashboard-widget.scss` | Shared analytics/ops widget surface styles |
| Per-widget `.scss` files | Encapsulated widget styling using shared partial |

Sidebar and login were not redesigned.

## Layout changes

### Section A – Welcome

- Compact flex header with title, subtitle, org + date meta
- Reporting period `mat-select` on the right (full width on narrow screens)

### Section B – KPI cards

- CSS Grid: `repeat(3, minmax(0, 1fr))` at ≥1200px
- Equal-height vertical cards: icon → value → label → supporting text
- Six live API-backed metrics preserved

### Section C – Analytics

- Two-column grid at ≥768px
- Project Status, Objectives Progress, Indicator Performance, Reporting Calendar

### Section D – Operational

- Two-column grid at ≥768px
- Pending Approvals + Recent System Activities

### Section E – Empty / loading

- Reusable empty states with restrained teal icon chip
- Loading / error paths unchanged functionally

## Responsive breakpoints

| Viewport | KPI | Analytics | Ops |
|---|---|---|---|
| ≥1200px | 3 columns | 2 columns | 2 columns |
| 768–1199px | 2 columns | 2 columns | 2 columns |
| <480px | 1 column | 1 column | 1 column |
| 480–767px | 2 columns | 1 column | 1 column |

## Testing results

| Check | Result |
|---|---|
| `ng build` (development) | Passed |
| `ng build` (production) | Passed |
| KPI overlap at 1440px | **0 overlaps** among 6 `app-stat-card` hosts; clean 3×2 grid |
| KPI layout at 1024px | 2-column grid; **0 overlaps**; no horizontal overflow |
| KPI layout at 390px | 1-column stack; **0 overlaps**; no horizontal overflow |
| API integration | Existing `DashboardService.overview()` retained |
| Fake statistics | None introduced |
| Sidebar | Untouched |

Geometry verification used `getBoundingClientRect()` against `.kpi-grid > app-stat-card` only (host nodes), confirming independent non-overlapping cells.

## Remaining issues

1. **Phase 3 metrics** (objectives progress / indicator performance) correctly show unavailable empty states until backend data exists.
2. **Pending approvals / activities** remain empty until workflow/audit payloads are populated.
3. **Reporting period selector** is UI-ready; period value is not yet wired as an API query parameter.
4. **Breadcrumb** null-guard was added; deeper breadcrumb UX polish is out of scope for this body renovation.

## Acceptance checklist

- [x] Six properly aligned KPI cards
- [x] Zero overlapping KPI elements
- [x] Consistent spacing and brand palette
- [x] Balanced analytics / operational widgets
- [x] Compact welcome section
- [x] Professional empty states
- [x] Responsive across desktop / tablet / mobile
- [x] Existing functionality preserved
- [x] Production build passes
