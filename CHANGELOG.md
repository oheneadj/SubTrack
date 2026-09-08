# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added
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

### Changed
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
