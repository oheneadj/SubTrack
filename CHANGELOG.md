# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Changed
- User show page's "Account History" is now paginated (10 per page) instead of silently capped at the 10 most recent entries with no way to see anything older. Tests: `UserShowActivityHistoryPaginationTest`

### Fixed
- Overview dashboard's "Critical Expirations" and "Expiring This Month" tables had no way to view a subscription individually — only a "Send Reminder" button, no link to the subscription itself. The client/project name is now a row-level link, plus icon-only view/remind buttons, matching the row-link style used elsewhere (renewal tracker, subscriptions list). Tests: `OverviewDashboardSubscriptionLinksTest`
- The "renewals paid directly never get counted as revenue" bug (previously fixed only on the Finance Dashboard's headline total) was actually present in three more places: `RevenueService` — used by *both* dashboards' 6-month trend, month-over-month change %, and the Revenue vs Expenses chart — and `OverviewDashboard`'s own separate, duplicated `financeStats()` calculation, which showed a different (invoice-only) "Total Revenue" number than the Finance Dashboard right next to it. Centralized all of it into `RevenueService::totalRevenue()`/`revenueForMonth()`, so every revenue figure app-wide now comes from one place and both dashboards always agree. Tests: `RevenueServiceTest` (including one asserting the two dashboards report an identical number)
- The password show/hide toggle didn't actually work: login/reset-password (and every other auth page — confirm-password, two-factor-challenge, forgot-password, verify-email) are rendered by plain Fortify controllers, not Livewire components, so they never receive Livewire's bundled Alpine.js — every `x-data`/`x-show`/`@click` on them was inert. Added an explicit Alpine.js `<script>` to the auth layout only, since every other layout already gets Alpine via Livewire and loading it twice on the same page breaks both instances

### Added
- Show/hide password toggle on the login page and the password reset page (both password fields — new password and confirmation). Added the missing `icon-eye-off` component (matching the existing Tabler-icon style) since only `icon-eye` existed before
- Overdue payment policy: two new settings, **Penalty Percentage** and **Grace Period (Days)**, under Notification Preferences. Once a subscription is actually overdue (nothing shown before expiry — nothing's been missed yet), the reminder email now states a clear "Payment Overdue Notice": the number of missed renewal cycles, the stated penalty percentage/amount that *would* apply on renewal (disclosure only — never charged to an actual invoice), and the exact grace-period deadline with a plain statement that the service is automatically cancelled with no cost or liability to the business if payment isn't received by then
- `Subscription::statedPenaltyPercentage`/`statedPenaltyAmount`/`formattedStatedPenaltyAmount` — penalty percentage × missed-cycles, for display only
- `Subscription::gracePeriodDeadline` — expiry date + configured grace period, null while not overdue
- `subtrack:check-expiries` now auto-cancels a subscription once its grace period deadline has passed, logging a `SubscriptionAutoCancelled` `DashboardActivityLog` entry with the missed-payment count
- Tests: `OverduePaymentPenaltyTest`
- `MonthlyRenewalTestDataSeeder` — 10 clearly-labeled `RecurringMonthly`/`OneTimeMonthly` subscriptions under a "Monthly Test Client", covering active, each reminder window (7/3 days), and overdue by 1/2/3 missed cycles — for testing the reminder-day filtering and missed-payments-count work without hand-creating records. Registered in `DatabaseSeeder`; safe to re-run (`updateOrCreate`)
- Subscriptions page: filter by Renewal Type (One-time, One-time Monthly/Annually, Recurring Monthly/Annually), alongside the existing service/status/date filters. Included in the CSV export's filtered query too, and in the shareable URL query string like the other filters
- `Subscription::missed_payments_count` — for an overdue subscription, how many renewal payments have actually been skipped (`days overdue ÷ cycle length`), not just a raw negative day count. Only meaningful for recurring types with a fixed cycle (`null` for bare `OneTime`, which has no cycle to count against — those still show days overdue). Replaces "-45 days" / "EXPIRED 45 DAYS AGO" style displays with e.g. "2 payments missed" across the subscriptions list, renewal tracker, subscription detail page, dashboard, and the reminder email itself. Tests: `MissedPaymentsCountTest`

### Fixed
- Neither the project detail page nor the provider detail page let you click through to a listed subscription — the row's action menu only had Edit/Delete, no view, and the service name wasn't a link either. Both now link the service name and add a view action, matching `subscription-index.blade.php`'s existing pattern. Tests: `SubscriptionRowLinkTest`

### Changed
- `Subscription::applicableReminderDays()` now keeps a configured day only if it falls within roughly the last third of that subscription's billing cycle, not just "under the cycle length." A 14-day reminder is a fine heads-up on a 365-day annual cycle, but on a 30-day monthly cycle it lands right around the halfway point — barely past the last renewal, not a meaningful warning — so it's now excluded for monthly while still kept for annual

### Fixed
- Found while re-examining reminders: `NotificationService::resolveInvoiceForReminder()` created a brand-new draft invoice on *every* expiry reminder for a subscription that had never been renewed before (no `Renewal` row yet, so nothing to detect "I already made one for this cycle" from) — a subscription with a 30/14/7-day reminder schedule got 3 duplicate draft invoices for the same amount. Added `invoice_items.subscription_id` so a pending draft/sent invoice already created for this subscription's current cycle is found and reused instead of creating another. Tests: `ReminderInvoiceDeduplicationTest`
- Notification Preferences' "Reminder Days" setting only ever made sense for annual subscriptions — a 30-day reminder is basically the entire cycle for a monthly subscription, firing right as the previous one started. Worse, `subtrack:check-expiries` never actually read this setting at all; it sent reminders on a hardcoded `[30, 7, 3, 1]` regardless of what was configured. Now the command reads `reminder_days`, and `Subscription::applicableReminderDays()` filters it per subscription down to whatever actually fits that subscription's own billing cycle (bare `OneTime`, with no fixed cycle, keeps every configured value)
- Tests: `SubscriptionReminderDaysTest`

### Fixed
- **Production-breaking**: `EmailLogger::track()` (used by all 5 non-Direct-Mailer mailables) called `$mailable->render()` on the same instance that then got queued — `render()` permanently mutates a Mailable via `prepareMailableForDelivery()`, which calls `withSymfonyMessage(Closure)` internally, so the queued job failed to serialize ("Serialization of 'Closure' is not allowed") the moment any of these actually hit a real queue connection (confirmed live via `ReceiptMail`, `InvoiceMail`, `SubscriptionReminderMail`, `UserInviteMail`, `ClientMagicLinkMail`). `Mail::fake()` never exercises real job serialization, so every existing test passed despite this. Fixed by rendering a `clone` of the mailable for the log snapshot instead of the instance itself; added `QueueSerializationTest`, which forces the `database` queue connection (not `Mail::fake()`) and reproduces the original crash when reverted
- Toast/notification copy across Direct Mailer reworded to plain language — no more "Queued", "batch", or "Check the Email Log for details" surfaced to the end user
- Direct Mailer's "Queued emails to N clients" toast never actually appeared: `<x-ui.toast>` lives in the outer layout, outside the Livewire component's DOM root, so a `session()->flash()` set from a `wire:click` action (no page navigation) never gets shown — Livewire only re-renders the component's own markup on that request, not the surrounding layout. Added a Livewire-event-driven toast (`$this->dispatch('notify', type: ..., message: ...)`, caught by a new Alpine listener in the layout) that works regardless of navigation, and switched `DirectMailer::send()`'s two flashes onto it. Also fixed `MailTemplateIndex::resetToDefault()`'s pre-existing `dispatch('notify', [...])` call, which used the wrong argument shape and would never have matched the listener either

### Added
- Direct Mailer batches now notify the sender (via the notification bell) once every email in the batch has left the Queued state — sending is async (a queued job can finish well after the person who clicked "Send" has moved on), so a same-request toast can only ever say "queued", never "delivered" or "failed". New `App\Services\EmailBatchNotifier`, hooked into both the `MessageSent` listener and `TracksEmailDelivery::failed()`, guarded by a cache lock so a batch is only ever notified once. Scoped to Direct Mailer sends only — the other mailables are always a batch of one sent as a direct consequence of the admin's own action, so a second notification would just be noise
- Email Log tracking extended to all 5 remaining mailers (previously Direct Mailer/`GenericClientMail` only): `ClientMagicLinkMail`, `UserInviteMail`, `InvoiceMail`, `SubscriptionReminderMail`, `ReceiptMail`. All now implement `ShouldQueue` (dedicated `emails` queue, tries/timeout/backoff) and get an `EmailLog` row before sending, via a new generic `App\Services\EmailLogger::track()` helper and shared `App\Mail\Concerns\TracksEmailDelivery` trait (Message-ID tagging + `failed()` handling), which `GenericClientMail` was also refactored onto to remove the now-duplicated logic
- `UserInviteMail`'s logged snapshot has the plaintext temporary password redacted (`[REDACTED]`) before storage — the real email still contains it, but it's never persisted to the audit table, per the project's rule against storing secrets at rest
- "Resend" in the Email Log UI is deliberately restricted to `GenericClientMail` (Direct Mailer) rows — the only mailable `DispatchClientMailAction::resend()` can reconstruct from a stored snapshot. `EmailLog::scopeResendable()` and the batch aggregate counts both enforce this, and `resend()` itself throws if misused
- Real delivery status via Brevo's webhook (not just "handed to SMTP"): a new `/webhooks/email/brevo` endpoint (shared-secret token, `BREVO_WEBHOOK_TOKEN`) receives delivered/hard_bounce/soft_bounce/blocked/invalid_email/spam/opened/click events and updates the matching `EmailLog` row. Each email is tagged with a ULID-based `Message-ID` header at send time so Brevo's webhook payload can be correlated back to it without ever exposing the internal integer id
- `EmailLogStatus` extended with `Delivered`, `Bounced`, `Blocked`, `Complained`; `EmailLog` gained `delivered_at`/`bounced_at`/`opened_at`/`clicked_at`. A late/out-of-order bounce event never overwrites an already-`Delivered` status. Resend is offered for `Failed`/`Bounced`/`Blocked` but deliberately not `Complained` — resending to someone who reported the message as spam isn't a delivery fix
- New `email_log_events` table stores every raw webhook payload per email (per the project's third-party-response-logging rule) — viewable via an "Events" button on the batch detail page showing each event's pretty-printed JSON
- Idempotency: redelivered Brevo webhooks (on non-200 responses) are only processed once, reusing the same `WebhookEvent` table the payment gateway webhooks already use
- Email Log (Direct Mailer only, for now): every send from the mailer — individual or bulk — is now recorded to a new `email_logs` table, one row per recipient grouped under a `batch_id`. A new "Email Log" admin page lists every batch with sent/queued/failed counts, and a batch detail page lists each individual email with its status and error message (if failed)
- Resend: a failed individual email can be resent from the batch detail page; "Resend All Failed" resends every failed email in a batch from either the batch list or the batch detail page — reusing the exact subject/body snapshot that was supposed to go out the first time, not a freshly re-rendered version
- `App\Actions\DispatchClientMailAction` — queues a `GenericClientMail` and creates its `EmailLog` row; the single place both the initial send and any resend go through
- A global `MessageSent` listener marks a tagged email's log row `Sent`; `GenericClientMail::failed()` marks it `Failed` with the error once retries are exhausted
- Tests: `EmailLogTest` (batch creation, sent/failed status transitions, individual and bulk resend), `BrevoWebhookTest` (auth, status transitions, idempotency, unknown message-id), extended `AdminPagesSmokeTest` and `DirectMailerTest` coverage

### Fixed
- Found and fixed a production-breaking bug introduced while building the above: tagging a queued mailable via `withSymfonyMessage(closure)` fails queue job serialization outright ("Serialization of 'Closure' is not allowed") the moment it's pushed to a real queue — switched to `GenericClientMail::headers()`, the serializable API meant for this
- Making the 5 remaining mailables `ShouldQueue` surfaced a second instance of the same class of bug: `MailTemplateIndex::sendTest()` builds a mock, unsaved `Subscription`/`Invoice` for slugs with no real record yet — Laravel forces `Mail::send()` on a `ShouldQueue` mailable through the queue regardless, which would try (and fail) to serialize a model with no primary key. Switched that one call site to `Mail::sendNow()`, which always sends in-process regardless of `ShouldQueue`
- Finance dashboard's "Total Revenue" and "Recent Payments Received" only ever counted paid Invoices — subscription renewals paid for directly (never invoiced) were silently excluded, even though "Profit" on the same dashboard already counted them. Now both revenue figures merge paid invoices with directly-paid renewals (`Renewal::whereNull('invoice_id')`, so a renewal already counted once via its linked invoice isn't double-counted)
- Recent Payments Received was displaying `$invoice->total_amount` (stored in cents) directly as dollars with no `/100` conversion — a 100x display bug on every row
- Tests: `FinanceDashboardRevenueTest` — merged revenue totals, and no double-counting for invoice-linked renewals

### Changed
- `x-ui.action-menu`'s built-in View/Edit/Delete pill buttons now match `x-ui.button`'s `xs` sizing exactly (`px-2 py-1`, `font-medium`, `gap-2`, no uppercase/tracking) — previously they used bespoke `px-3 py-1.5 font-bold uppercase gap-1.5` classes, so they visibly clashed with any slotted `x-ui.button` sitting right next to them (e.g. invoice-index, renewal-tracker, client-index's Email button)
- Client detail page's "Communication" dropdown (Send Custom Email / Send Renewal Reminder) now uses the same menuitem styling as `x-ui.action-menu`'s own dropdown mode, instead of bespoke `px-3 py-2` classes
- `x-ui.action-menu`'s Edit pill/menuitem now uses `blue-*` to match `x-ui.button`'s `soft` variant palette exactly, instead of `sky-*` (a different, barely-distinguishable but technically separate color family)
- `x-ui.confirm-modal` now renders its Confirm/Cancel buttons through `x-ui.button` instead of hand-rolled `<button>` markup — was rendering noticeably larger (`px-4 py-2`) than every other button in the app (`sm` default is `px-3 py-1.5`)
- Standardized table row actions app-wide onto the shared `x-ui.action-menu` component — fixes 5 different inconsistent patterns found in an audit (hand-rolled buttons with bespoke colors in `renewal-tracker`, a 4-button row instead of a collapsible menu in `invoice-index`, 3 different single-icon-button styles with no delete option in `client-show`'s three tables, a view button living outside the menu in `user-index`, and a slotted button with hardcoded white-text overrides in `client-index`)
- Direct Mailer & Mail Templates pages restyled to match the app's design system: replaced one-off `rounded-3xl`/`shadow-*`/`text-[10px]` custom styling with the standard `.input`/`.select`/`.textarea` primitives from `app.css`, `x-ui.page-header`, and `x-ui.empty-state`; removed a duplicated `p-6` wrapper on the templates index (layout already applies page padding)
- Removed `shadow-*` utility classes from `x-ui.card`, `x-ui.stat-card`, `x-ui.data-table`, `x-ui.toolbar`, and several ad-hoc card-styled containers (public invoice page, client login/invoice portal, client/project forms) app-wide
- App background changed from `bg-slate-50` to `bg-gray-100`
- `DirectMailer`: "Select All" now selects every client matching the current search across all pages (previously only the 6 on the current page); searching resets pagination back to page 1; recipient list pagination switched to `simplePaginate` (compact Prev/Next) so it fits the narrow sidebar without overflowing
- `GenericClientMail` now implements `ShouldQueue` (dedicated `emails` queue, `tries=3`, `timeout=30`, `backoff=[10,30,60]`, logs to `Log::error` on final failure via `failed()`) — bulk sends from Direct Mailer no longer block the request; local dev's `queue:listen` now listens on `emails,default`
- User show page: removed raw internal `id` from the Meta Data card; fixed the initials avatar rendering as an oval instead of a circle (missing explicit height)
- `DirectMailer::send()` is now rate limited to 3 attempts per minute per user
- `DirectMailer`: selecting a template while there's an unsaved manual draft (typed subject/body) now prompts for confirmation instead of silently overwriting it
- Extracted `GenericClientMail`'s placeholder-rendering logic into `App\Services\ClientMailPersonalizer`, shared with the new Direct Mailer preview so both stay in sync

### Added
- User show page "Account History" now shows real `ActivityLog` entries for the user (as actor or subject) instead of a static placeholder; `toggleActive`/`confirmPasswordReset` now log to `ActivityLog`
- `warning` toast type wired into the global flash-message layout (previously only `success`/`error`)
- `DirectMailer`: "Preview" button showing the rendered subject/body with placeholders filled in for the first selected recipient, before sending to everyone
- `ClientFactory`, `MailTemplateFactory`
- Tests: `DirectMailerTest`, `MailTemplateIndexTest` — auth/authorization, validation, happy path, select-all-across-pages, search-resets-pagination, rate limiting, manual-edit guard, preview rendering

### Fixed
- Two missing icon components — `icon-file-x` (crashed the public invoice page and the client invoice portal with "Unable to locate a class or view for component") and `icon-chevron-down` (client invoice portal's expand/collapse toggle) — added both, matching the app's existing Tabler-icon SVG style
- Tests: `NoMissingIconComponentsTest` — statically scans every Blade file for `<x-icon-*>` references with no matching component, so a missing icon can't sit broken again until someone happens to render that exact page (which is how `icon-file-x` went unnoticed all session)

### Changed
- Client detail page: subscriptions moved out of a capped, summary-only sidebar card (5 max, active-only) into their own full section in the main column, directly below Projects — a proper table (service/domain, project, provider, expiry, status) covering every subscription, not just the active ones
- Added an "Add Subscription" action to the client detail page, matching the existing "New Project" — both create with the client pre-filled via `?clientId=`
- Tests: `ClientShowProjectsSubscriptionsTest`

### Added
- `App\Livewire\Clients\ClientForm` — extracted client create/edit out of `ClientIndex` into its own standalone component (mirroring how `ProjectForm` already works), shared as a modal between the clients index and a client's own detail page. Opening "Edit Client" from the detail page no longer navigates away to the clients index to do it — it opens in place, exactly like every other edit modal in the app
- Tests: `ClientFormModalTest`

### Fixed
- "Edit Client" on the client detail page had unreadable text — no variant was set (defaulting to solid `btn-primary`, white text) while custom classes forced the background to white too, leaving white-on-white. Switched to the `soft` variant (blue text on a light background), matching the same fix already applied to the Email button
- **The actual reason "Edit Project" still didn't work after the previous fix**: `$dispatchTo(...)` was moved into `@click` to fix the wire:click-vs-server-action bug, but `$dispatchTo` isn't a real Alpine magic — Livewire only registers `$dispatch` globally; `dispatchTo` only exists as `$wire.dispatchTo(...)` or the global `Livewire.dispatchTo(...)`. The bare `$dispatchTo(...)` call threw a silent `ReferenceError` in the browser console, so the modal still opened (the *other* half of the same click handler, `$dispatch('open-modal', ...)`, ran fine first) but the form never received the project to load and rendered empty. Fixed every instance to use `Livewire.dispatchTo(...)` — the same pattern already working correctly for the modal's own Cancel button elsewhere in these files — and updated `action-menu.blade.php`'s JS-vs-server-action detection to recognize the `Livewire.` prefix too
- "Add Subscription" (×2) and "New Invoice" on the project detail page passed the project/client's raw internal `id` as the `?projectId=`/`?clientId=` query param — the target forms (`SubscriptionForm`, `InvoiceBuilder`) look those up by `ulid`, so the project/client never pre-filled and the link silently landed on an empty form. Same bug class as the earlier row-action fixes, just hiding inside a `route()` query array instead of a `wire:click` argument — the earlier sweep's search pattern didn't catch this shape, so it survived that pass
- Tests: `ProjectShowLinksTest`

### Added
- `<x-ui.toast>` — a reusable success/error flash toast (auto-dismissing, bottom-right), wired once into the shared app layout. Replaces ~10 differently-styled, hand-copied flash-message blocks scattered across individual pages — several of which duplicated the layout's own inline banner, so the message showed twice on those pages. Now every page shows the same toast automatically after any action that flashes `success`/`error`, including every delete/edit action fixed in the previous commit
- Tests: `FlashToastTest`

### Fixed
- The "Email" button on the clients list had a white icon but no explicit text color on its label, inheriting a dark blue that was nearly unreadable against the button's background — set explicitly to white to match the icon. Its hover state had the same problem one layer deeper: a custom `hover:bg-blue-100` override turned the background light on hover while the (now-white) text stayed white, making the label disappear entirely on hover. Removed the conflicting overrides so the button's own `btn-primary` styling applies cleanly (solid blue, white text, darker blue on hover)
- Audited every table/index page (clients, projects, providers, subscriptions, invoices, users, activity logs, mail templates) for row actions not using the shared `<x-ui.button>`/`<x-ui.action-menu>` components — none found. The only remaining raw `class="btn ..."` markup left in the app is on auth-page submit buttons and a dev-only component preview page, neither of which are table actions
- **The real cause of the "edit/delete returns 404" report**: nearly every row action across the app (client edit/delete, project edit/delete, provider edit/delete, invoice download/send/mark-paid, activity log details, dashboard reminder buttons, mail-recipient selection, user delete/resend-invite/toggle-active) passed the model's raw internal `id` into an action method that looks the record up by `ulid` (e.g. `Client::where('ulid', $id)->firstOrFail()`). The mismatch means `firstOrFail()` always threw `ModelNotFoundException`, which Laravel renders as a 404 — this was a pre-existing, systemic bug (present since the first commit), not something introduced this session, but it explains the reported symptom exactly. Fixed every call site to pass `->ulid` instead of `->id`
- Subscription deletion from the project detail page was calling a `confirmDelete()` method that didn't exist on `ProjectShow` at all — implemented it properly (confirm modal + delete), matching the pattern used elsewhere
- Two Blade views (`activity-log-index.blade.php`, `direct-mailer.blade.php`) had reverted to an earlier, buggy inline-query version of themselves during the app-wide card/button rollout — the rollout agents worked from file snapshots that predated this session's own earlier fixes to those same files, and copying their output back wholesale silently undid that earlier work. Restored both to use their component computed properties instead of inline `@php` queries
- Tests: `UlidActionParamsTest`
- `UserFactory` never set `is_active`, `requires_password_change`, or `role` explicitly, relying on the DB column defaults — but Eloquent doesn't refresh a model's in-memory attributes with server-side defaults after an insert, so every factory-created user read `is_active` as `null`. `EnsureUserIsActive` middleware treats a falsy `is_active` as a disabled account and force-logs the request out to `/login`. This was silently masking nearly every HTTP-level test in the suite (11 "unrelated" pre-existing failures turned out to share this one root cause) and made it impossible to properly test real page routes — fixed by setting all three explicitly in the factory's `definition()`
- Verified (now that HTTP-level testing actually works) that the reported 404s on the Clients/Projects/Providers/Subscriptions/Invoices show/edit routes are not a routing or model-binding bug — every route resolves correctly end-to-end. If it's still happening in the deployed app, it's most likely a stale `route`/`view`/`config` cache from before this session's changes were deployed — worth ruling out with `php artisan route:clear && view:clear && config:clear` before looking further
- Delete-confirmation modal backdrop (`confirm-modal.blade.php`, plus the subscription's renewal/receipt modals, and the provider add/edit/delete modals) had no explicit stacking order between its blurred backdrop layer and its content layer — harmless as long as the modal never opened, but the previous bug meant it never had, so this was never visually exercised. Now that modals actually open, gave the backdrop `z-0` and the content wrapper `z-10` so the backdrop blur can never visually cover the modal itself
- "New Project" and "Edit Project" buttons on the projects list, project detail, and client detail pages never worked — they put a client-side `$dispatchTo(...)` JS call (used to tell the project-form modal's child component what to load) directly into `wire:click`, which only understands server-side PHP action calls. Combined it into the existing `@click` handler on each button instead
- `action-menu.blade.php`'s `editAction` prop had the same bug baked in for any future caller passing a JS expression — it now detects a `$`-prefixed action and routes it to `@click` (merged with the existing `editModalId` modal-open dispatch) instead of always assuming `wire:click`
- Removed a dead `$confirmDelete` boolean property on `ProjectIndex` that was never read anywhere but shadowed the `confirmDelete()` action method's name — confusing and pointless
- Tests: `ActionMenuEditDispatchTest`
- Delete-confirmation modals on the subscriptions and projects list pages never opened — `confirm-modal.blade.php` listens for the `open-modal` browser event and checks `$event.detail.id`, but both pages dispatched it as a bare string (`dispatch('open-modal', 'confirm-delete-subscription')`) or a positional array (`dispatch('open-modal', ['id' => '...'])`), neither of which produces a `.id` property on the event detail. Both now use Livewire's named-argument dispatch (`dispatch('open-modal', id: '...')`), matching the one call site that already worked (`UserShow`)
- Tests: `DeleteConfirmationModalTest` — asserts the exact dispatched event shape so this can't silently regress again

### Added
- Rolled out `<x-ui.card>`, `<x-ui.button>`, `<x-ui.toolbar>` (introduced for the subscriptions pages) across the rest of the admin app: clients, dashboard, invoices, mail templates, projects, providers, users, activity logs, settings, and the notification list — 23 pages converted from repeated raw markup to the shared components
- `button` component gained `info` and `lg` variants/sizes to cover the two button shapes the rollout needed that didn't exist yet (Send-to-client invoice action, the mail composer's large send button)
- `AdminPagesSmokeTest` — most of these 23 pages had zero test coverage before this rollout; this smoke test renders every one of them (via `Livewire::test()`) so a broken Blade/component tag anywhere in the admin app fails loudly instead of silently

### Fixed
- Two `<x-ui.button>` usages had a raw `@if`/`@endif` (and one `@disabled`) directive embedded directly in the component tag's attribute list, which Blade's component-tag compiler cannot parse (it silently mis-compiled the whole file into unbalanced PHP) — both rewritten using the `:disabled="..."` boolean-attribute binding Blade actually supports there
- A "Close" button in the activity log detail modal had been mapped to the solid-background `secondary` button variant during the rollout instead of the visually-equivalent unstyled/`ghost` variant — fixed to `ghost` to preserve the original look

### Removed
- Client filter dropdown on the subscriptions list (`filterClientId`, `filterableClients`) — sorting by client name (added earlier) covers browsing by client without a separate filter

### Fixed
- Toolbar layout: filter selects/date inputs no longer get squeezed by flexbox alongside the search box (`shrink-0` on fixed-width filters, `flex-1` on the search input), and the Export CSV button no longer wraps its label onto two lines (`whitespace-nowrap`)

### Added
- Three new reusable Blade components, per CLAUDE.md §10 (check for a reusable component before building new UI): `<x-ui.card>` (title/actions-slot header, `:padding` toggle for table-wrapping cards), `<x-ui.button>` (`as="a"|"button"`, `variant`, `size`, `circle`, `full`, `soft` props — covers every button/link shape used on the subscription pages), `<x-ui.toolbar>` (the search-input-plus-filters bar above a data table; also fixes a pre-existing bug where the search icon wasn't positioned relative to its wrapper)

- `SubscriptionObserver`, `RenewalObserver`, `ReceiptObserver` — the dashboard activity feed already had icon/rendering support for `SubscriptionCreated`, `SubscriptionExpiring`, `SubscriptionExpired`, and `RenewalConfirmed` but nothing ever recorded them; these observers wire that up (subscription created, status transitions to Expiring/Expired, a renewal confirmed)
- `ActivityEventType::ReceiptGenerated` — new dashboard event for receipt generation, plus its icon/color mapping
- `Subscription::effectiveClientIdWithoutLoading()` — resolves the effective client ID without lazy-loading the `client`/`project.client` relations (used by the new observers, which run before those relations are eager loaded)
- `Receipt` model now uses `LogsActivity`, so receipt create/update/delete shows up in the generic audit trail (Activity Logs admin page) like every other financial record
- Tests: `DashboardActivityLoggingTest`

- `SubscriptionShow::viewReceipt()` (inline) and `downloadReceipt()` (attachment) — view/download actions on each row of the receipts list, regenerating the PDF first if it's missing
- The "Project / Client" column on the subscriptions list is now sortable by the subscription's effective client name (handles both direct `client_id` and via-project linkage)

- `SubscriptionRenewalType` enum (`OneTime`, `OneTimeMonthly`, `OneTimeAnnually`, `RecurringMonthly`, `RecurringAnnually`) with `expiryFrom()` date-math and `isRecurring()` helpers — foundation for auto-generated expiry dates and the renewal-tracker/receipts work that follows
- `subscriptions.renewal_type` (defaults to `RecurringAnnually` for existing rows) and `subscriptions.notes` columns
- Tests: `SubscriptionRenewalTypeTest`
- `Project::unrelatedFor(Client $client)` — get-or-create the client's catch-all "Unrelated" project, used whenever a subscription is saved without a real project selected
- Tests: `UnrelatedProjectTest`

- `form-input` Blade component gained a `:live` prop (mirroring `form-select`), used so the subscription form recalculates expiry date as soon as purchase date changes
- `SubscriptionShow::saveNotes()` — inline notes editing on the subscription page
- Tests: `RenewalTrackerExcludesOneTimeTest`

### Changed
- `SubscriptionForm::save()` now attaches a subscription to the client's "Unrelated" project instead of leaving `project_id` null when no project is picked
- `SubscriptionForm`: added Renewal Type and Notes fields; expiry date auto-computes (and becomes read-only) for renewal types with an implied duration, stays manual for bare One-time
- `SubscriptionShow`: displays renewal type, hides "Process Renewal" for non-recurring subscriptions, defaults the renewal increment (months vs years) to match the subscription's billing cycle, shows "Unrelated" instead of blank when no project is linked
- `RenewalTracker` no longer lists non-recurring (one-time) subscriptions — they don't need renewal processing
- Tests: `SubscriptionIndexSearchFilterExportTest`

- `receipts` table + `Receipt` model — an immutable, snapshotted proof-of-payment record per subscription
- `ReceiptNumberService` (year-scoped sequential numbers, e.g. `RCT-2026-001`) and `ReceiptPdfService` (mirrors `InvoicePdfService`), plus `resources/views/pdf/receipt.blade.php`
- `GenerateReceiptAction` — creates the receipt + PDF in a single DB transaction, then queues `ReceiptMail` to the client
- "Generate Receipt" action on the subscription page (amount defaults to renewal cost, editable; optional notes) plus a running list of past receipts for that subscription
- Tests: `GenerateReceiptTest`

### Changed
- Subscription pages (index, show, form) now use the new `card`/`button`/`toolbar` components instead of repeated raw markup — adopted here first as the reference implementation; the rest of the app still uses the old inline classes and can be migrated separately
- `SubscriptionIndex` search now also matches client name and project name (previously domain/provider only)
- `SubscriptionIndex` gained client and renewal-date-range filters, alongside the existing service-type/status filters
- CSV export rewritten to the requested column set: Client Name, Client Email, Subscription Name, Renewal Type, Subscription Date, Renewal Date, Status — filters/search apply identically to the export as to the on-screen list
- Adopted new CLAUDE.md rules (soft-delete unique-value mutation, Blade design/logic separation, webhook idempotency, payment polling fallback) into the existing codebase
- `Provider` and `Invoice` models now mutate their unique column (`name`, `invoice_number`) on soft delete (`{original}-deleted-{id}`), freeing the value for reuse by a new record
- `webhook_events` table + `WebhookEvent` model — deduplicates redelivered provider webhook events by (gateway, event_id) before they reach gateway-specific handling
- `PaymentGateway::webhookEventId()` and `PaymentGateway::pollStatus()` contract methods, implemented on `StripeGateway`
- `payments:poll-pending` scheduled command (every 5 minutes) — active polling fallback for payments still pending 15+ minutes after checkout, covering webhooks that never arrive
- Tests: `SoftDeleteUniqueMutationTest`, `WebhookIdempotencyTest`

### Changed
- Moved inline Blade-view business logic (live model queries, collection filtering) into computed properties on their owning Livewire components, per the new Blade design/logic separation rule: `DirectMailer::selectedClientModels()`, `ActivityLogIndex::selectedLog()`, `RenewalTracker::subscriptionToRenew()`

## [0.5.0] - 2026-07-02

### Added
- Policy classes for all authorization-sensitive models: `ClientPolicy`, `ProjectPolicy`, `SubscriptionPolicy`, `InvoicePolicy`, `ProviderPolicy`, `RenewalPolicy`, `UserPolicy`, `MailTemplatePolicy`, `ActivityLogPolicy` — all auto-discovered by Laravel's convention-based policy resolution
- Standard-access policies (`Client`, `Project`, `Subscription`, `Invoice`, `Provider`, `Renewal`) allow all authenticated users (single-tenant app)
- Admin-only policies (`User`, `MailTemplate`, `ActivityLog`) restrict access to super admins via `isSuperAdmin()`; `UserPolicy::delete` additionally prevents self-deletion

### Changed
- `emails/invoice-mail.blade.php` and `pdf/invoice.blade.php` updated to use `formatted_total_amount`, `formatted_subtotal`, `formatted_tax_amount`, `formatted_unit_price`, `formatted_total` accessors (completing the money formatting audit)
- `InvoiceMail` mailable updated to use `$invoice->formatted_total_amount` in template variable interpolation

### Notes
- Rate limiting: all public auth routes (login, 2FA) are already rate-limited by Fortify (5 attempts/min); no additional endpoints require rate limiting as all other routes sit behind `auth` middleware
- Form Requests: not applicable — this is a Livewire-only app; Livewire inline validation (`#[Validate]` attributes, `$rules` arrays) is the correct pattern for this stack

## [0.4.0] - 2026-07-02

### Added
- ULID columns and `HasPublicUlid` trait added to `MailTemplate`, `ActivityLog`, and `DashboardActivityLog` models (migration + backfill)
- Formatted money accessors on `Invoice` (`formatted_subtotal`, `formatted_tax_amount`, `formatted_total_amount`), `InvoiceItem` (`formatted_unit_price`, `formatted_total`), `Subscription` (`formatted_purchase_cost_usd`, `formatted_renewal_cost_usd`), `Renewal` (`formatted_provider_cost_usd`, `formatted_client_cost_usd`, `formatted_margin`)

### Changed
- Money columns (`subscriptions.purchase_cost_usd`, `subscriptions.renewal_cost_usd`, `invoices.subtotal`, `invoices.tax_amount`, `invoices.total_amount`, `invoice_items.unit_price`, `invoice_items.total`, `renewals.provider_cost_usd`, `renewals.client_cost_usd`) converted from `decimal(8,2)` to `unsignedBigInteger` storing integer minor units (cents); existing data multiplied ×100 on migration
- All Livewire components updated: dollar↔cent conversion at the DB boundary only (load: ÷100 for editing, save: ×100; aggregate stats: ÷100 before passing to views)
- All blade views updated to use formatted accessors (`$model->formatted_X`) instead of raw `number_format($model->column, 2)` calls
- `InvoiceMail`, `pdf/invoice.blade.php`, and `emails/invoice-mail.blade.php` updated to use formatted accessors
- `MailTemplateIndex`, `ActivityLogIndex` Livewire methods now accept ULIDs instead of integer IDs
- `user-show.blade.php` now displays `$user->ulid` instead of `$user->id`

## [0.3.0] - 2026-07-02

### Added
- ULID strategy: added `HasPublicUlid` trait that auto-generates a `ulid` column on creation and uses it for route model binding (internal bigint PK preserved)
- Migration `add_ulid_columns_to_tables` — adds `ulid` string(26) with unique index to: `users`, `clients`, `projects`, `subscriptions`, `invoices`, `providers`; back-fills existing rows on `up()`, fully reversed on `down()`

### Changed
- Applied `HasPublicUlid` trait to `Client`, `Project`, `Subscription`, `Invoice`, `Provider`, `User` models
- All Livewire component action methods that previously accepted `int $id` now accept `string $ulid` and resolve the integer PK internally: `ClientIndex::edit/openDeleteModal`, `ProviderIndex::edit/openDeleteModal`, `ProviderShow::deleteSubscription`, `ProjectIndex::confirmDelete`, `SubscriptionIndex::confirmDelete`, `RenewalTracker::openRenewalModal`, `UserIndex::openToggleModal/resendInvite/confirmDelete`, `InvoiceIndex::downloadPdf/sendInvoice/markAsPaid`
- `SubscriptionIndex` bulk-select now stores and operates on ULIDs instead of integer IDs
- `DirectMailer` now stores client ULIDs in `selectedClients` and resolves `subscriptionId`/`clientId` query params by ULID
- `SubscriptionForm::mount` resolves `?projectId=` query param by ULID
- `InvoiceBuilder::mount` resolves `?clientId=` and `?projectId=` query params by ULID
- `ProjectForm::openProjectModal` and `mount` now accept string ULIDs for `$id` and `$clientId`; resolve integer PKs internally
- All blade views updated to pass `$model->ulid` instead of `$model->id` in `wire:click` arguments, query strings, and event dispatches

## [0.2.0] - 2026-07-01

### Changed
- Added `declare(strict_types=1)` to all 73 files under `app/` — enforces strict typing across the entire codebase
- Added explicit return types to 20 methods across Livewire components, models, and traits that were missing them
- Added `Model::preventLazyLoading()` to `AppServiceProvider` — throws in local/staging, logs in production
- Added `@property` docblocks to `Client`, `Project`, `Invoice`, `Subscription` models for PHPStan + IDE type safety
- Wrapped `InvoiceBuilder::save()` multi-step write (invoice create/update + items) in `DB::transaction`
- Added `wire:key` to all `@foreach` loops across 16 Livewire blade views (~30 loops fixed)
- Fixed `fputcsv` call in `InvoiceIndex::export()` — enum value now cast to string via `->value`
- Fixed `InvoiceNumberService::generate()` — cast int to string before `str_pad()`
- Fixed `Subscription::getDaysUntilExpiryAttribute()` — cast `diffInDays()` float return to int
- Fixed `EnsureSuperAdmin` middleware — typed local variable for `$request->user()` to satisfy PHPStan
- Fixed `NotificationBell` — corrected `@var` annotation to `User|null` to reflect nullable auth state

### Added
- Installed `nunomaduro/larastan` (PHPStan for Laravel) as a dev dependency
- Added `phpstan.neon` configuration at level 5 with Larastan extension
- Removed Flux UI from `recovery-codes.blade.php` — replaced with plain Tailwind/FlyonUI equivalents

### Fixed
- `InvoiceIndex::export()` — `$invoice->status` now uses `->value` so `fputcsv` receives a string not an enum

## [0.1.0] - 2026-07-01

### Added
- Initial application scaffolding: User, Client, Project, Subscription, Renewal, Invoice, InvoiceItem, Provider, Setting, MailTemplate, ActivityLog, DashboardActivityLog models
- Dashboard with activity logging
- Invoice management with DomPDF export
- Subscription and renewal tracking
- Notification system for accepted invitations
