# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

PowerCabs Corporate — a plain PHP (no framework, no Composer, no build step) web portal where a company logs in and books/manages taxi rides for its employees. It is one of several apps against a shared Supabase project; the dispatcher app (`pw_dispatcher`) and the driver app (`pw_app_driver`) read and write the same `rides` table. Comments referencing `pw_dispatcher/order.php` mean logic was copied from that app and must be kept in sync manually.

## Commands

There is no build, test, or lint tooling. PHP 8.3 is at `C:/MAMP/bin/php/php8.3.1/php.exe`.

```bash
# Serve locally (from repo root)
"C:/MAMP/bin/php/php8.3.1/php.exe" -S localhost:8000

# Syntax-check a file after editing
"C:/MAMP/bin/php/php8.3.1/php.exe" -l php/save_ride.php
```

Third-party libs are vendored, not installed: `assets/stripe-php/` (require `init.php`) and `php/PHPMailer/src/` (require the three files manually). Front-end libs (Bootstrap 5, Bootstrap Icons, jQuery, DataTables 1.13, supabase-js v2, html2canvas, jsPDF) are loaded from CDNs in each page's `<head>`/footer — there is no bundler.

## Architecture

### Request shape

Every user-facing page is a top-level `.php` file that: `session_start()` → `require auth/supabase.php` → redirect to `login.php` if `$_SESSION['user']` is missing → fetch rows from Supabase server-side → set `$pageTitle` → `require 'modules/navbar.php'` → render Bootstrap HTML with inline `<style>`/`<script>`. Pages are large and self-contained; their JavaScript lives inline except for the shared files in [js/](js/).

AJAX/form endpoints live in [php/](php/) and follow a fixed contract: `session_start()`, `require ../auth/supabase.php`, check `$_SESSION['user']['cid']`, then either echo JSON (`{success, message, ...}`) or set `$_SESSION['success']`/`$_SESSION['error']` and redirect back (those flash keys are consumed and rendered by [modules/toast.php](modules/toast.php)).

### Data access

All data goes through `SupabaseClient` in [auth/supabase.php](auth/supabase.php) — a thin cURL wrapper over the PostgREST REST API with `select/insert/update/delete` and `eq.`-only filters (`deleteWithOperators` handles other operators). It uses the **service-role key**, so there is no RLS protection: **every query must filter by `cid` from the session**, and update/delete endpoints must first `select` the row with `['id' => ..., 'cid' => $cid]` to confirm ownership. Credentials are hardcoded in [auth/config.php](auth/config.php) (Supabase URL + keys, SMTP). [php/connection.php](php/connection.php) is a leftover MySQL connection and is not used by anything.

### The `rides` table is shared and schema-tolerant

Corporate bookings are rows in the shared `rides` table, distinguished by `source`:

- `source = 'corporate'` — regular booking (created by [php/save_ride.php](php/save_ride.php))
- `source = 'corporate meet_and_greet'` — airport booking (created by [php/save_meetgreet.php](php/save_meetgreet.php))

Any read of `rides` must filter on both `cid` and one of those two `source` values, otherwise driver/dispatcher rides leak into the corporate UI. Canonical column mapping used everywhere: `pickup_addr`, `dest_addr`, `fare_eur`, `distance_km`, `duration_min`, `ride_type`, `payment_method`, and `enroute_at` (reused to store the corporate *scheduled pickup time*). The `normalizeCorporateRide()` / `isCorporateSource()` pair is duplicated verbatim in [home.php](home.php), [rideHistory.php](rideHistory.php), and [php/rides_snapshot.php](php/rides_snapshot.php) — change all three together.

Because the shared schema drifts, write endpoints use an **adaptive insert/update loop**: required canonical columns are always sent; "optional" columns (`employee`, `guest_name`, `notes`, `terminal`, `cancel_reason`, …) are dropped one at a time when PostgREST reports `Could not find the 'x' column` / `column "x" of relation`, then the request is retried (up to ~12 attempts). Preserve this pattern when adding fields, and add new fields to the *optional* array unless the column is guaranteed to exist. Reads are similarly tolerant — see the `CID`/`cid`/`company_id` fallbacks in `corporate_row_filters_try()` and the `ride_types` icon/label column probing in [bookRide.php](bookRide.php) and [meetGreet.php](meetGreet.php).

Other tables: `corporate` (accounts; `pass` is a `password_hash`), `corporate_employees` (id is `<CompanyNameNoSpaces><4 digits>`), `employee_ride_summary`, `ride_types`, `password_resets`.

### Live ride updates

[js/realtime-rides.js](js/realtime-rides.js) drives the dashboard and history tables. It subscribes to Supabase Realtime `postgres_changes` on `rides` (anon key + `cid` injected via `window.RIDES_REALTIME_CONFIG`), with a 60 s poll as a safety net, plus `BroadcastChannel`/`localStorage` events so a booking in one tab refreshes the others. Every trigger funnels into a single-flight, debounced, 4 s-throttled fetch of [php/rides_snapshot.php](php/rides_snapshot.php), which re-renders `#rides-body` and re-initialises the DataTable via `window.initRidesDataTable()` ([js/script.js](js/script.js)). Pages can inject extra table cells by defining `window.rideRowExtraCells(ride)`. A `Pending → Assigned` transition raises a persistent notification via `window.showPersistentNotification` (localStorage-backed, scoped by `window.PC_USER_CID`, defined in [modules/toast.php](modules/toast.php)).

`home.php` and `rideHistory.php` also fire [php/cleanup_rides.php](php/cleanup_rides.php) on load, which auto-cancels this company's pending/scheduled rides whose `enroute_at` is in the past.

### Fare calculation

Fares are computed **client-side only** and never validated on the server. `calculateFare()` in [js/bookride.js:188](js/bookride.js#L188) (and its twin inside [meetGreet.php](meetGreet.php)) is a copy of `pw_dispatcher/order.php`: €3 initial + day/night base and per-km/per-min rates (day = 08:00–20:00 by pickup hour) × a per-ride-type multiplier. Distance/duration come from the Google Maps Directions API; the Maps key is inline in the page `<script src>`.

### Pages and their endpoints

| Page | Purpose | Endpoints |
|---|---|---|
| [login.php](login.php) | login (AJAX) | `auth/login.php`, `auth/session.php`, `auth/logout.php` |
| [forgot-password.php](forgot-password.php) | 4-step OTP reset | `php/send-otp.php`, `verify-otp.php`, `resend-otp.php`, `reset-password.php` |
| [home.php](home.php) | dashboard stats + recent rides | `php/rides_snapshot.php`, `php/cleanup_rides.php` |
| [bookRide.php](bookRide.php) | Maps-based booking form | `php/save_ride.php` |
| [meetGreet.php](meetGreet.php) | airport meet & greet booking | `php/save_meetgreet.php` |
| [rideHistory.php](rideHistory.php) | full history + status/cancel | `php/edit_ride.php` |
| [employee.php](employee.php) | employee CRUD (modals in [modals/](modals/)) | `php/addemployee.php`, `editemployee.php`, `deleteemployee.php` |
| [invoice.php](invoice.php) | pick employee + date range, build PDF | `php/invoice_rides.php` |
| [profile.php](profile.php) | company profile & password | `php/update_profile.php`, `php/change_password.php` |

Dead code, do not extend: [php/login.php](php/login.php) (superseded by `auth/login.php`), [php/submit_ride.php](php/submit_ride.php) (superseded by `save_ride.php`), [php/connection.php](php/connection.php), [php/create_payment.php](php/create_payment.php) (hardcoded €0.60 Stripe intent, not wired to any page).

Invoices are generated entirely in the browser: `invoice_rides.php` returns only **completed** rides for one employee, tagged `ride_kind` = `corporate_ride` or `meet_and_greet`; [invoice.php](invoice.php) renders an off-screen A4 template — Meet & Greet rides go in their own table while totals stay combined — then rasterises it with html2canvas and emits a PDF with jsPDF.

### Conventions

- Sidebar highlighting and the floating "Book Ride" button key off `$pageTitle` matching the `match` strings in [modules/sidebar.php](modules/sidebar.php) and the exclusion list in [modules/fab-bookride.php](modules/fab-bookride.php) — set `$pageTitle` before requiring the navbar.
- Brand orange is `#f37a20`; type scale lives in the `--fs-*` CSS variables in [global.css](global.css). Per-page styling is a shadcn-ish neutral palette in inline `<style>` blocks.
- New bookings should call `notifyDashboardAndRideHistoryUpdated()` (or post to the `powercab-corporate-rides` BroadcastChannel) so other tabs refresh.
- Server-side failures on read paths are deliberately swallowed so a page still renders with empty data; write paths log via `error_log('[endpoint] ...')` and return a short user-facing message (see `mapSupabaseErrorToUserMessage()` in [php/save_ride.php](php/save_ride.php) for the Postgres-code → message mapping).
