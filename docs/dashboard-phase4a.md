# Arenaku Courtside — Phase 4A

## Scope

Shared management shell, admin/caretaker overviews and profile presentation only.
Operational CRUD templates, public pages, authentication templates, controllers,
routes, models, migrations and vendor assets remain unchanged.

The shell retains Bootstrap 4, Font Awesome, jQuery, SB Admin 2 and the existing
DataTables scripts/initialization. No Bootstrap 5 CSS/JS is imported. Foundation
CSS uses the same named color/type/spacing tokens as arenaku-public.css, mirrored
in an isolated dashboard adapter so public selectors do not affect legacy forms.
New component selectors style the overview/profile; operational table markup and
DataTables behavior are deferred to Phase 4B.

## Components

- nav-link: existing route names with route-based aria-current state.
- task-link: labeled operational shortcuts with descriptive text.
- feedback: existing flash messages and linked Laravel error summary.
- profile-field: persistent labels, old values, inline errors and autocomplete.
  Password values are never rendered back into fields.

Admin sees both supplied counts and administrative shortcuts. Penjaga sees only
the supplied assigned-venue count and existing operational links. This does not
claim that booking records are filtered by venue ownership.

The mobile drawer has an explicit toggle/close control, Escape handling, focus
containment and focus restoration; background workspace is inert while open.
Without JavaScript, navigation stays visible. At 768px it becomes a persistent
sidebar. Profile fields split into two columns at 768px; account summary sits
beside the form at 1024px. Reduced-motion preferences disable transitions.

## Verification

- Blade compilation and JavaScript syntax: passed.
- Diff whitespace check: passed; no operational view/backend/vendor files changed.
- PHPUnit direct runner: 9 tests, 57 assertions passed. Three new in-memory view
  tests cover admin/caretaker menus, supplied counts, profile update method/CSRF,
  old input, validation output and password non-disclosure.

```powershell
php -r 'require "vendor/autoload.php"; exit((new PHPUnit\TextUI\Application)->run(["phpunit", "--no-configuration", "tests"]));'
```

The direct runner avoids the standard command's bootstrap-path problem observed
in earlier phases and skips the legacy PHPUnit XML configuration. The existing
homepage smoke test requires local database access. No records were created.

Production build was attempted but stopped before compilation: node_modules /
cross-env is absent. webpack.mix.js maps an effectively empty app.scss to
public/css/app.css; no dependency installation, Mix changes or generated-asset
replacement was performed. New dashboard CSS/JS are served directly.

Browser access to /home redirected to login. No authenticated test session or
credentials were available. Styled checks at 375/768/1024/1440px, keyboard drawer
behavior, logout modal, live profile updates and operational-page integration
remain unverified. Static review does not replace these checks.

## Guidelines review

Reviewed the affected foundation against the current
[Web Interface Guidelines](https://raw.githubusercontent.com/vercel-labs/web-interface-guidelines/main/command.md):
native links/buttons, labels, accessible names, decorative-icon hiding, skip
navigation, focus indicators, error association, image dimensions, long-content
wrapping, password autocomplete and reduced-motion support. Corrected email
spellchecking during review. No remaining actionable static findings within 4A;
authenticated browser checks remain outstanding as listed above.

## Existing backend issues — not modified

- Several management routes lack explicit authentication/role middleware in the
  inspected route declarations. Sidebar role visibility is not authorization.
- BookingController lists all booking records without caretaker ownership scope;
  the new operational links retain the previous navigation access only.
- ProfileController redirects silently when the current password does not match;
  no error exists for the view to display in that branch.
- ProfileController ignores an empty phone value, so clearing a saved phone number
  cannot be implemented through presentation changes alone.
- Success/validation messages retain the backend's existing language/locale.
- Existing asset_url defaults to `/`; subdirectory deployments may load assets
  from the wrong root. The dashboard retains asset() without changing config.

Stop here. Phase 4B requires explicit approval.
