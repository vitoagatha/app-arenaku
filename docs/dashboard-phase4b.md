# Courtside management pages — Phase 4B

## Scope and files

Updated existing views under dashboard/lapangan (index/create/edit), courts
(index/create/edit), users (index/create/edit), booking (index/edit), and jadwal
(venue selection and schedule list/modals). Reused the Phase 4A shell and tokens.
No controller, route, model, migration, authentication, authorization, public view
or third-party vendor file changed.

Added dashboard components: field, delete-action, venue-image, image-upload,
status, management-table and schedule-modal. Existing feedback accepts an optional
non-linked error list for schedule pages, where inputs live inside modal dialogs.

Extended arenaku-dashboard.css/js and the existing application-owned
js/demo/datatables-demo.js. DataTables retains its default search/sort/pagination
configuration; language and empty-result messages are now Indonesian. Empty
states are outside tbody, so no colspan placeholder breaks table initialization.

## Behavior

- Forms keep original route names, POST/PUT methods, CSRF, input names, upload
  encoding, role values and schedule-status values. Old input is displayed after
  validation, except passwords/files. No duration restrictions were added.
- File input shows a local image preview; failed/missing stored images have a
  text fallback. Accept hints match the existing upload validator.
- Deletion uses a native browser confirmation containing the relevant record
  name (court/date/time for schedules). DELETE form semantics are preserved.
- Booking labels show actual pending/confirmed/canceled values as
  Menunggu/Dikonfirmasi/Dibatalkan, with no payment implications.
- Schedule inputs have unique IDs per modal. On submit, sessionStorage remembers
  only the modal ID. After validation redirects, JavaScript reopens that modal
  and applies server-provided old input to that form only. No UI-only input is
  submitted to backend create/update methods. If storage/JavaScript is unavailable,
  errors remain visible but automatic edit-modal restoration is unavailable.
- Buttons show pending-submit feedback and reset on back/forward restoration.
  This is UI feedback, not server-side duplicate-submission protection.
- Tables retain all existing columns; booking adds its already-existing status.
  Scroll regions are keyboard-focusable; labels, column headers and action names
  are accessible. DataTables search/sorting/pagination remain available.

## Verification

- All tests: 14 passed, 156 assertions. Five new in-memory view tests cover all
  index/create/edit pages, original field/method contracts, old input, password
  non-disclosure, empty lists, status labels and unique schedule-modal IDs.
- Blade compilation, both modified JavaScript syntax checks and git diff --check
  passed. No records were created/updated/deleted for testing.
- Production build attempted; cross-env/node_modules is absent, so no compilation
  occurred. Legacy Mix still targets an effectively empty app.scss. No generated
  CSS, vendor assets or build configuration was overwritten.
- Browser /home redirected to login; no authorized admin/caretaker session was
  available. Screenshots at 375/768/1024/1440px, DataTables runtime, modal reopening,
  image preview, confirmation dialogs and live CRUD submissions remain unverified.
  PHP view tests do not substitute for these interaction checks.

Reviewed the affected markup against the current
[Web Interface Guidelines](https://raw.githubusercontent.com/vercel-labs/web-interface-guidelines/main/command.md).
Corrected heading hierarchy, contextual action labels, missing labels/IDs, focus
regions and missing image dimensions. Styling remains Bootstrap 4 compatible.

## Existing backend concerns — not modified

- Existing management authentication/role enforcement and caretaker booking-query
  ownership scope require a separate backend review; hiding menus is not access control.
- Schedule creation/update do not validate interval order or overlap. Court options
  remain the existing globally supplied collection; no ownership rules were added.
- Booking duration is independent of its schedule interval; booking creation lacks
  transactional availability locking, and deletion does not reopen the schedule.
- Required related records are assumed by existing table views. Missing relations
  need a separate data-integrity policy; no fabricated replacements were introduced.
- User update ignores an empty phone value, so an existing phone cannot be cleared.
- Server-generated validation/success message language follows existing backend locale.
- Existing resource show routes reference missing show templates. No new show pages
  were invented; current UI continues to use the existing index/create/edit routes.
- Existing asset_url root configuration may break subdirectory asset loading.

No further phase was started.
