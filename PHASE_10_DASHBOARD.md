# Phase 10 — Dashboard (Detailed Build Prompt)

> Feed this to Antigravity as a new phase after Phase 9 is complete.
> Read GEMINI.md, DESIGN_SYSTEM.md, and PROGRESS.md before starting.

---

## Overview

Build the main dashboard for SubTrack. This is the first screen the user sees after login.
It must answer four questions in order of urgency, top to bottom:

1. Is anything expiring in the next 7 days? → Act now
2. What is expiring in the next 30 days? → Plan ahead
3. Who has not paid me? → Chase money
4. What does my revenue look like? → Business health

Every section maps to one of these questions. Nothing on this screen is decorative.

---

## New Database Migration Required

Before building any UI, create this migration:

```php
// database/migrations/xxxx_create_activity_logs_table.php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
    $table->string('event_type', 60); // subscription.expiring, invoice.paid, reminder.sent, client.created, etc.
    $table->string('description');    // Human-readable: "Reminder sent to Accra Foods (domain, 2 days)"
    $table->json('meta')->nullable(); // Any extra context: invoice_id, subscription_id, days_left etc.
    $table->timestamps();
});
```

---

## New Enum Required

```php
// app/Enums/ActivityEventType.php
namespace App\Enums;

enum ActivityEventType: string
{
    case SubscriptionExpiring  = 'subscription.expiring';
    case SubscriptionExpired   = 'subscription.expired';
    case SubscriptionCreated   = 'subscription.created';
    case ReminderSent          = 'reminder.sent';
    case InvoiceCreated        = 'invoice.created';
    case InvoiceSent           = 'invoice.sent';
    case InvoicePaid           = 'invoice.paid';
    case InvoiceOverdue        = 'invoice.overdue';
    case RenewalConfirmed      = 'renewal.confirmed';
    case ClientCreated         = 'client.created';
}
```

---

## New Model Required

```php
// app/Models/ActivityLog.php
class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'event_type', 'description', 'meta'];

    protected $casts = [
        'event_type' => ActivityEventType::class,
        'meta'       => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Helper to create a log entry from anywhere in the app
    public static function record(
        ActivityEventType $type,
        string $description,
        ?int $clientId = null,
        array $meta = []
    ): self {
        return static::create([
            'event_type'  => $type,
            'description' => $description,
            'client_id'   => $clientId,
            'meta'        => $meta,
        ]);
    }
}
```

---

## Activity Logging — Where to Call It

Add `ActivityLog::record(...)` calls in these existing service methods:

```php
// In NotificationService::sendRenewalReminder()
ActivityLog::record(
    ActivityEventType::ReminderSent,
    "Reminder sent to {$client->name} ({$subscription->service_type->value}, {$daysLeft} days)",
    $client->id,
    ['subscription_id' => $subscription->id, 'days_left' => $daysLeft]
);

// In NotificationService::sendInvoice()
ActivityLog::record(
    ActivityEventType::InvoiceSent,
    "Invoice {$invoice->invoice_number} sent to {$invoice->client->name}",
    $invoice->client_id,
    ['invoice_id' => $invoice->id, 'total' => $invoice->total_usd]
);

// In InvoiceBuilder::createInvoice() after invoice is created
ActivityLog::record(
    ActivityEventType::InvoiceCreated,
    "Invoice {$invoice->invoice_number} created — \${$invoice->total_usd}",
    $invoice->client_id,
    ['invoice_id' => $invoice->id]
);
```

Also add activity logging inside Model Observers. Create these observer classes:

```php
// app/Observers/ClientObserver.php
public function created(Client $client): void
{
    ActivityLog::record(
        ActivityEventType::ClientCreated,
        "New client added: {$client->name}",
        $client->id
    );
}

// app/Observers/InvoiceObserver.php
public function updated(Invoice $invoice): void
{
    if ($invoice->wasChanged('status')) {
        if ($invoice->status === InvoiceStatus::Paid) {
            ActivityLog::record(
                ActivityEventType::InvoicePaid,
                "Invoice {$invoice->invoice_number} marked as paid",
                $invoice->client_id,
                ['invoice_id' => $invoice->id, 'total' => $invoice->total_usd]
            );
        }
        if ($invoice->status === InvoiceStatus::Overdue) {
            ActivityLog::record(
                ActivityEventType::InvoiceOverdue,
                "Invoice {$invoice->invoice_number} is overdue",
                $invoice->client_id,
                ['invoice_id' => $invoice->id]
            );
        }
    }
}
```

Register observers in `AppServiceProvider::boot()`:
```php
Client::observe(ClientObserver::class);
Invoice::observe(InvoiceObserver::class);
```

---

## Revenue Query

Add this method to a new `RevenueService` class:

```php
// app/Services/RevenueService.php
class RevenueService
{
    public function lastSixMonths(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            $date  = now()->subMonths($monthsAgo);
            $total = Invoice::where('status', InvoiceStatus::Paid)
                ->whereYear('issued_date', $date->year)
                ->whereMonth('issued_date', $date->month)
                ->sum('total_usd');

            return [
                'label'  => $date->format('M'),
                'total'  => (float) $total,
                'year'   => $date->year,
                'month'  => $date->month,
            ];
        });

        return $months->toArray();
    }

    public function currentMonthTotal(): float
    {
        return (float) Invoice::where('status', InvoiceStatus::Paid)
            ->whereYear('issued_date', now()->year)
            ->whereMonth('issued_date', now()->month)
            ->sum('total_usd');
    }

    public function previousMonthTotal(): float
    {
        return (float) Invoice::where('status', InvoiceStatus::Paid)
            ->whereYear('issued_date', now()->subMonth()->year)
            ->whereMonth('issued_date', now()->subMonth()->month)
            ->sum('total_usd');
    }

    public function monthOverMonthChange(): array
    {
        $current  = $this->currentMonthTotal();
        $previous = $this->previousMonthTotal();
        $diff     = $current - $previous;
        $pct      = $previous > 0 ? round(($diff / $previous) * 100) : 0;

        return [
            'current'    => $current,
            'previous'   => $previous,
            'diff'       => $diff,
            'percentage' => $pct,
            'direction'  => $diff >= 0 ? 'up' : 'down',
        ];
    }
}
```

---

## Livewire Component

```php
// app/Livewire/Dashboard/OverviewDashboard.php
class OverviewDashboard extends Component
{
    // Inject services via mount, not constructor (Livewire requirement)
    public array $revenueData    = [];
    public array $revenueChange  = [];

    public function mount(RevenueService $revenue): void
    {
        $this->revenueData   = $revenue->lastSixMonths();
        $this->revenueChange = $revenue->monthOverMonthChange();
    }

    #[Computed]
    public function criticalSubscriptions()
    {
        return Subscription::critical()
            ->with('project.client')
            ->orderBy('expiry_date')
            ->get();
    }

    #[Computed]
    public function warningSubscriptions()
    {
        return Subscription::warning()
            ->with('project.client')
            ->orderBy('expiry_date')
            ->get();
    }

    #[Computed]
    public function recentInvoices()
    {
        return Invoice::with('client')
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function activityFeed()
    {
        return ActivityLog::with('client')
            ->latest()
            ->take(6)
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'critical'       => Subscription::critical()->count(),
            'warning'        => Subscription::warning()->count(),
            'healthy'        => Subscription::healthy()->count(),
            'awaiting'       => Renewal::where('payment_status', PaymentStatus::Invoiced)->count(),
            'overdue'        => Invoice::where('status', InvoiceStatus::Overdue)->count(),
            'total_clients'  => Client::count(),
        ];
    }

    public function sendReminder(int $subscriptionId): void
    {
        $subscription = Subscription::with('project.client')->findOrFail($subscriptionId);
        $daysLeft     = $subscription->days_until_expiry;

        app(NotificationService::class)->sendRenewalReminder($subscription, $daysLeft);

        session()->flash('success', "Reminder sent to {$subscription->project->client->name}.");
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview-dashboard')
            ->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
```

---

## Blade View Structure

```
resources/views/livewire/dashboard/overview-dashboard.blade.php
```

Build the view using ONLY components from `resources/views/components/ui/`.
Do not write one-off HTML. Every repeated pattern is a component.

### Layout Specification

```
TOPBAR
  Left:  "Dashboard" title + today's date in muted text
  Right: Export button (ghost), New Invoice button (primary)

ALERT BANNER
  Show ONLY when $stats['critical'] > 0
  Red background, pulsing dot, message with count, "View all" link
  Use component: <x-ui.alert-banner>  ← create this new component

STATS ROW  (6 columns, responsive: 3 on tablet, 2 on mobile)
  1. Critical     → variant="critical"  icon="alert-triangle"
  2. Expiring     → variant="warning"   icon="clock"
  3. Healthy      → variant="healthy"   icon="circle-check"
  4. Awaiting     → variant="info"      icon="currency-dollar"
  5. Overdue      → variant="critical"  icon="calendar-x"
  6. Clients      → variant="neutral"   icon="users"

TWO COLUMN SECTION
  Left:  Critical Expirations table (order by expiry_date ASC)
  Right: Expiring This Month table  (order by expiry_date ASC)

  Each table row:
    - Client name (bold) + project name (muted, smaller)
    - Service type badge
    - Days remaining pill (red if ≤7, amber if ≤30)
    - Inline "Remind" button → wire:click="sendReminder({{ $sub->id }})"

THREE COLUMN SECTION
  Left (wider):   Recent Invoices table (last 5 invoices)
  Middle:         Monthly Revenue chart (Chart.js bar, 6 months)
  Right:          Recent Activity feed (last 6 events)
```

---

## New Blade Components to Create

These do not exist yet. Create them in `resources/views/components/ui/`.

### `<x-ui.alert-banner>`

**Props:** `message`, `count`, `actionLabel`, `actionLink`

```blade
@props(['message', 'count' => 0, 'actionLabel' => 'View all', 'actionLink' => '#'])
<div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-4">
    <span class="relative flex h-2.5 w-2.5 flex-shrink-0">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
    </span>
    <p class="text-sm text-red-800 flex-1">{{ $message }}</p>
    <a href="{{ $actionLink }}" class="text-xs text-red-600 underline flex-shrink-0">
        {{ $actionLabel }} →
    </a>
</div>
```

### `<x-ui.days-pill>`

**Props:** `days` (integer)

```blade
@props(['days'])
@php
  $classes = $days <= 7
    ? 'bg-red-50 text-red-600'
    : 'bg-amber-50 text-amber-600';
  $label = $days === 1 ? '1 day' : "{$days} days";
@endphp
<span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full {{ $classes }}">
    {{ $label }}
</span>
```

### `<x-ui.activity-item>`

**Props:** `type` (ActivityEventType), `description`, `time`

```blade
@props(['type', 'description', 'time'])
@php
$config = match($type) {
    \App\Enums\ActivityEventType::InvoicePaid      => ['bg-green-50',  'text-green-600',  'circle-check'],
    \App\Enums\ActivityEventType::InvoiceSent      => ['bg-blue-50',   'text-blue-600',   'file-invoice'],
    \App\Enums\ActivityEventType::InvoiceCreated   => ['bg-blue-50',   'text-blue-600',   'file-plus'],
    \App\Enums\ActivityEventType::InvoiceOverdue   => ['bg-red-50',    'text-red-600',    'calendar-x'],
    \App\Enums\ActivityEventType::ReminderSent     => ['bg-amber-50',  'text-amber-600',  'bell'],
    \App\Enums\ActivityEventType::ClientCreated    => ['bg-purple-50', 'text-purple-600', 'user-plus'],
    \App\Enums\ActivityEventType::RenewalConfirmed => ['bg-teal-50',   'text-teal-600',   'refresh'],
    default                                        => ['bg-slate-50',  'text-slate-500',  'activity'],
};
@endphp
<div class="flex items-start gap-3 py-2.5 border-b border-slate-100 last:border-0">
    <div class="p-1.5 rounded-lg flex-shrink-0 {{ $config[0] }}">
        <x-tabler-{{ $config[2] }} class="w-3.5 h-3.5 {{ $config[1] }}" />
    </div>
    <div class="min-w-0">
        <p class="text-xs text-slate-700 leading-snug">{!! $description !!}</p>
        <p class="text-xs text-slate-400 mt-0.5">{{ $time }}</p>
    </div>
</div>
```

---

## Revenue Chart — Implementation Notes

Use Chart.js loaded via CDN. Pass data from Livewire to the chart using a blade variable
encoded as JSON. Alpine.js initialises the chart on mount.

```blade
{{-- In the revenue panel --}}
<div
    x-data="revenueChart({{ Js::from($revenueData) }})"
    x-init="init()"
>
    <div style="position:relative; height:100px;">
        <canvas x-ref="canvas"></canvas>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
function revenueChart(data) {
    return {
        init() {
            const labels = data.map(d => d.label);
            const values = data.map(d => d.total);
            const last   = values.length - 1;

            new Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: values.map((_, i) =>
                            i === last ? '#0f172a' : 'rgba(15,23,42,0.08)'
                        ),
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' $' + ctx.raw.toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 } } },
                        y: { display: false }
                    }
                }
            });
        }
    }
}
</script>
```

---

## Design Rules for This Page

Read DESIGN_SYSTEM.md. Additionally for this page:

1. **Alert banner** renders ONLY when `$stats['critical'] > 0`. Hidden otherwise — no empty space.
2. **Table rows** must use `wire:loading` state on the Remind button: disable + show spinner while sending.
3. **Revenue chart** current month bar is always dark (`#0f172a`), previous months are muted (`rgba(15,23,42,0.08)`).
4. **Stats grid** is `grid-cols-6` on desktop, `grid-cols-3` on tablet (`md:`), `grid-cols-2` on mobile (`sm:`).
5. **Activity timestamps** use `$log->created_at->diffForHumans()` — always relative ("2 hours ago"), never absolute.
6. **Days pill** colour is determined by the `<x-ui.days-pill>` component alone — never inline.
7. **Empty states** — if no critical subscriptions, the critical panel shows `<x-ui.empty-state icon="circle-check" title="All clear" message="No subscriptions expiring in the next 7 days." />`.
8. **Responsive layout** — two-col and three-col sections stack to single column on mobile (`grid-cols-1`).
9. **Page title slot** passes `"Dashboard"` to the topbar component. No duplicate h1.

---

## Files to Create or Modify

```
NEW files:
  database/migrations/xxxx_create_activity_logs_table.php
  app/Enums/ActivityEventType.php
  app/Models/ActivityLog.php
  app/Observers/ClientObserver.php
  app/Observers/InvoiceObserver.php
  app/Services/RevenueService.php
  resources/views/components/ui/alert-banner.blade.php
  resources/views/components/ui/days-pill.blade.php
  resources/views/components/ui/activity-item.blade.php

MODIFY files:
  app/Livewire/Dashboard/OverviewDashboard.php  ← full rebuild per spec above
  app/Services/NotificationService.php          ← add ActivityLog::record() calls
  app/Services/InvoicePdfService.php            ← add ActivityLog::record() call
  app/Livewire/Invoices/InvoiceBuilder.php      ← add ActivityLog::record() call
  app/Providers/AppServiceProvider.php          ← register observers
  resources/views/livewire/dashboard/overview-dashboard.blade.php  ← full rebuild
```

---

## Completion Checklist

Before marking this phase done, verify every item:

```
[ ] Migration created and run
[ ] ActivityLog model, enum, and observers created
[ ] Observers registered in AppServiceProvider
[ ] ActivityLog::record() calls added in all 3 service methods
[ ] RevenueService created with all 3 methods
[ ] OverviewDashboard Livewire component rebuilt
[ ] All 3 new UI components created (alert-banner, days-pill, activity-item)
[ ] Alert banner only renders when critical count > 0
[ ] Remind button has wire:loading state
[ ] Revenue chart renders correctly with real data
[ ] Stats grid is responsive (6 → 3 → 2 columns)
[ ] Empty states render on both subscription tables when lists are empty
[ ] Activity feed shows relative timestamps
[ ] PROGRESS.md updated with all new files
```

---

*End of Phase 10 dashboard prompt.*
