# Courtside booking journey — Phase 3

The availability, customer-information and submission-result views now share the
existing public Courtside layout, tokens, typography, logo, buttons and image
fallback behavior. No routes, controllers, models, migrations, dashboard or vendor
files were changed.

## UI behavior

- Venue details use the stored photo, name, address and expandable description.
- Date selection retains old input and displays the actual result date in Indonesian.
- Schedules are grouped by court ID. Only `ready` records link to booking;
  `booking`, `closed` and unknown states are non-actionable text entries.
- The summary precedes the form in reading order. Inputs retain old values,
  include persistent labels and connect validation errors with their fields.
- Submission buttons show progress until navigation; back/forward restoration
  resets their state. This is interaction feedback, not booking collision protection.
- The result says “Booking berhasil dicatat” without claiming approval or payment.

## Verification

- Blade compilation, JavaScript syntax and whitespace checks passed.
- All six tests passed (25 assertions) using the installed PHPUnit runner directly:

```powershell
php -r 'require "vendor/autoload.php"; exit((new PHPUnit\TextUI\Application)->run(["phpunit", "--no-configuration", "tests"]));'
```

The standard `php artisan test` entry failed to resolve its bootstrap path in this
environment. The direct runner skips the legacy XML configuration. The homepage
smoke test requires access to the existing local database; the four new view tests
use in-memory fixtures and do not create database records.

The production build could not start because node_modules/cross-env is absent.
Public CSS and JS are served directly and do not require compilation.

Browser navigation confirmed the real venue detail renders. At 375px the local
subdirectory URL loaded without CSS/images: config/app.php defaults asset_url to
`/`, so requests resolve at the host root rather than /app_arenaku/public. The
separate localhost:8000 preview could not be reached. Styled screenshots at
375px, 768px and 1440px, keyboard interactions and a complete live booking
submission remain unverified. No production booking was created for testing.

## Existing backend concerns (not changed)

- Booking creation does not recheck/lock the selected schedule before insertion.
  Concurrent submissions or direct confirmation URLs can target unavailable slots.
- Duration is an independent integer; it is not reconciled with the slot interval.
  No new minimum, maximum or multiple-slot behavior was added to the UI.
- The booking table defaults status to pending; recording is not approval/payment.
- Result rendering follows a POST directly, so refresh may resubmit a booking.
- Availability queries return all records for a date, including booked/closed slots.
- Going back to date selection retains the date via a query parameter, but the
  user must submit the existing POST form again to load its schedules.
- Validation message language follows the existing application locale.
- Search pagination behavior identified in the earlier audit remains unchanged.
