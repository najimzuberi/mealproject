# Dashboard Component Architecture

```
frontend/src/app/
  core/
    config/navigation.config.ts     # Sidebar IA + permissions + availability
    guards/auth.guard.ts
    interceptors/auth.interceptor.ts
    models/api.model.ts
    services/*                      # Typed API clients
  shared/components/                # Reusable shell & state UI
  layout/
    main-layout/                    # Authenticated shell
    auth-layout/                    # Login shell
  features/
    auth/login/
    dashboard/
      dashboard-overview/
      *-widget/
    coming-soon/
  app.routes.ts                     # Lazy-loaded feature routes
```

## Shell composition

`MainLayout` owns Material sidenav state (handset overlay vs desktop side mode) and hosts:

1. `AppSidebar` — permission-filtered, config-driven nav
2. `AppToolbar` — breadcrumbs, search, org switcher, notifications, profile
3. `RouterOutlet` — feature pages

## Dashboard composition

`DashboardOverview` loads one aggregate payload and projects it into:

- 6× `StatCard`
- 6× feature widgets (chart / empty / table / list)

## Extending modules

1. Add or update an entry in `NAVIGATION_CONFIG`
2. Set `availability: 'live'` and `route`
3. Lazy-load a feature component in `app.routes.ts`
4. Reuse shared page header / empty / error states
