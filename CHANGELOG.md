# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Fixed
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
