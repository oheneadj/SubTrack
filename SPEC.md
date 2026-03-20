# Client Subscription & Asset Manager — Master Build Specification

---

## 🧠 Agent Instructions

You are an expert Laravel 12 developer. Your job is to build this application **exactly as specified**, phase by phase. Do not skip ahead. Complete each phase fully before moving to the next.

### Core Principles You Must Follow

1. **DRY** — Never repeat logic. If something is used twice, it belongs in a component, service, or trait.
2. **KISS** — Simple, readable code over clever code. Junior developers should be able to read it.
3. **Modularity** — Every UI element is a Blade component. Every business rule is a Service class. Every repeated query is a Model scope.
4. **Consistency** — All UI is built from the component library defined in `DESIGN_SYSTEM.md`. Never write one-off inline styles or duplicate HTML patterns.
5. **Naming** — Follow Laravel conventions strictly. Models are singular PascalCase. Tables are plural snake_case. Livewire components are PascalCase. Blade components are kebab-case.

### Before Writing Any Code

- Read this entire file first.
- Read `DESIGN_SYSTEM.md` before writing any Blade or Livewire view.
- Build in the exact phase order listed below.
- After each phase, list the files created and confirm the phase is complete.

---

## 🗂️ Project Overview

**App Name:** SubTrack (internal working title — make it configurable via Settings)
**Purpose:** A private internal tool for a web agency to track client subscriptions (domains, hosting, SSL, maintenance), automate renewal reminders, and generate professional USD invoices.
**Users:** Single-user or small team (no public-facing access).
**Currency:** USD throughout. No multi-currency.

---

## 🧱 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Reactive UI | Livewire 3 |
| JS Interactivity | Alpine.js (bundled with Livewire) |
| UI Framework | FlyonUI (DaisyUI-based, Tailwind CSS) |
| Icons | Tabler Icons via `codeat3/blade-tabler-icons` |
| PDF Generation | barryvdh/laravel-dompdf |
| Auth | Laravel Breeze (Blade stack) |
| Database | MySQL or SQLite (dev) |
| Email | Laravel Mail (SMTP — configurable in Settings) |
| Scheduler | Laravel native scheduler |

---

## 📁 Full File Structure

```
app/
├── Console/
│   └── Commands/
│       └── CheckSubscriptionExpiries.php
├── Livewire/
│   ├── Dashboard/
│   │   └── OverviewDashboard.php
│   ├── Clients/
│   │   ├── ClientIndex.php
│   │   └── ClientForm.php
│   ├── Projects/
│   │   ├── ProjectIndex.php
│   │   └── ProjectForm.php
│   ├── Subscriptions/
│   │   ├── SubscriptionIndex.php
│   │   └── SubscriptionForm.php
│   ├── Renewals/
│   │   └── RenewalTracker.php
│   ├── Invoices/
│   │   ├── InvoiceIndex.php
│   │   └── InvoiceBuilder.php
│   └── Settings/
│       └── AppSettings.php
├── Mail/
│   ├── SubscriptionReminderMail.php
│   └── InvoiceMail.php
├── Models/
│   ├── Client.php
│   ├── Project.php
│   ├── Subscription.php
│   ├── Renewal.php
│   ├── Invoice.php
│   ├── InvoiceItem.php
│   └── Setting.php
└── Services/
    ├── InvoicePdfService.php
    ├── InvoiceNumberService.php
    └── NotificationService.php

resources/
├── views/
│   ├── components/           ← ALL reusable Blade UI components live here
│   │   ├── layouts/
│   │   │   ├── app.blade.php
│   │   │   └── guest.blade.php
│   │   ├── ui/
│   │   │   ├── page-header.blade.php
│   │   │   ├── stat-card.blade.php
│   │   │   ├── badge-status.blade.php
│   │   │   ├── badge-payment.blade.php
│   │   │   ├── data-table.blade.php
│   │   │   ├── empty-state.blade.php
│   │   │   ├── confirm-modal.blade.php
│   │   │   ├── form-input.blade.php
│   │   │   ├── form-select.blade.php
│   │   │   ├── form-textarea.blade.php
│   │   │   └── action-menu.blade.php
│   │   └── nav/
│   │       ├── sidebar.blade.php
│   │       └── topbar.blade.php
│   ├── livewire/
│   │   ├── dashboard/
│   │   │   └── overview-dashboard.blade.php
│   │   ├── clients/
│   │   │   ├── client-index.blade.php
│   │   │   └── client-form.blade.php
│   │   ├── projects/
│   │   │   ├── project-index.blade.php
│   │   │   └── project-form.blade.php
│   │   ├── subscriptions/
│   │   │   ├── subscription-index.blade.php
│   │   │   └── subscription-form.blade.php
│   │   ├── renewals/
│   │   │   └── renewal-tracker.blade.php
│   │   ├── invoices/
│   │   │   ├── invoice-index.blade.php
│   │   │   └── invoice-builder.blade.php
│   │   └── settings/
│   │       └── app-settings.blade.php
│   ├── mail/
│   │   ├── subscription-reminder.blade.php
│   │   └── invoice-mail.blade.php
│   └── pdf/
│       └── invoice.blade.php

routes/
├── web.php
└── console.php

database/
└── migrations/
    ├── 0001_create_clients_table.php
    ├── 0002_create_projects_table.php
    ├── 0003_create_subscriptions_table.php
    ├── 0004_create_renewals_table.php
    ├── 0005_create_invoices_table.php
    ├── 0006_create_invoice_items_table.php
    └── 0007_create_settings_table.php
```

---

## 🗄️ Database Migrations

Create all migrations in this exact order.

### clients
```php
Schema::create('clients', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->string('phone')->nullable();
    $table->string('company_name')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### projects
```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained()->cascadeOnDelete();
    $table->string('project_name');
    $table->text('description')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### subscriptions
```php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->enum('service_type', ['Domain', 'Hosting', 'SSL', 'Maintenance', 'Other']);
    $table->string('provider');
    $table->string('domain_name')->nullable();
    $table->date('purchase_date');
    $table->date('expiry_date');
    $table->decimal('purchase_cost_usd', 10, 2);
    $table->decimal('renewal_cost_usd', 10, 2);
    $table->enum('status', ['Active', 'Expiring', 'Expired', 'Cancelled'])->default('Active');
    $table->timestamps();
    $table->softDeletes();
});
```

### renewals
```php
Schema::create('renewals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
    $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
    $table->date('due_date');
    $table->decimal('provider_cost_usd', 10, 2);
    $table->decimal('client_cost_usd', 10, 2);
    $table->enum('payment_status', ['Pending', 'Invoiced', 'Paid', 'Renewed', 'Lapsed'])->default('Pending');
    $table->date('payment_received_date')->nullable();
    $table->date('renewal_confirmed_date')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### invoices
```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained();
    $table->string('invoice_number')->unique();
    $table->date('issued_date');
    $table->date('due_date');
    $table->decimal('subtotal_usd', 10, 2)->default(0);
    $table->decimal('total_usd', 10, 2)->default(0);
    $table->enum('status', ['Draft', 'Sent', 'Paid', 'Overdue'])->default('Draft');
    $table->string('pdf_path')->nullable();
    $table->timestamps();
});
```

### invoice_items
```php
Schema::create('invoice_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
    $table->foreignId('renewal_id')->nullable()->constrained()->nullOnDelete();
    $table->string('description');
    $table->string('period')->nullable();
    $table->decimal('amount_usd', 10, 2);
    $table->timestamps();
});
```

### settings
```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->timestamps();
});
```

---

## 🧬 Eloquent Models

### Client.php
```php
class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'company_name'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(Subscription::class, Project::class);
    }

    // Scopes
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                     ->orWhere('email', 'like', "%{$term}%")
                     ->orWhere('company_name', 'like', "%{$term}%");
    }
}
```

### Project.php
```php
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['client_id', 'project_name', 'description'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
```

### Subscription.php
```php
class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'service_type', 'provider', 'domain_name',
        'purchase_date', 'expiry_date', 'purchase_cost_usd',
        'renewal_cost_usd', 'status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date'   => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    // Scopes
    public function scopeCritical($query)
    {
        return $query->where('expiry_date', '<=', now()->addDays(7))
                     ->where('status', '!=', 'Cancelled');
    }

    public function scopeWarning($query)
    {
        return $query->whereBetween('expiry_date', [now()->addDays(8), now()->addDays(30)])
                     ->where('status', '!=', 'Cancelled');
    }

    public function scopeHealthy($query)
    {
        return $query->where('expiry_date', '>', now()->addDays(30))
                     ->where('status', 'Active');
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getTrafficLightAttribute(): string
    {
        if ($this->days_until_expiry <= 7)  return 'critical';
        if ($this->days_until_expiry <= 30) return 'warning';
        return 'healthy';
    }
}
```

### Renewal.php
```php
class Renewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id', 'invoice_id', 'due_date',
        'provider_cost_usd', 'client_cost_usd', 'payment_status',
        'payment_received_date', 'renewal_confirmed_date', 'notes',
    ];

    protected $casts = [
        'due_date'                 => 'date',
        'payment_received_date'    => 'date',
        'renewal_confirmed_date'   => 'date',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getMarginAttribute(): float
    {
        return $this->client_cost_usd - $this->provider_cost_usd;
    }
}
```

### Invoice.php
```php
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'invoice_number', 'issued_date',
        'due_date', 'subtotal_usd', 'total_usd', 'status', 'pdf_path',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'due_date'    => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('amount_usd');
        $this->update([
            'subtotal_usd' => $subtotal,
            'total_usd'    => $subtotal,
        ]);
    }
}
```

### InvoiceItem.php
```php
class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'renewal_id', 'description', 'period', 'amount_usd',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function renewal(): BelongsTo
    {
        return $this->belongsTo(Renewal::class);
    }
}
```

### Setting.php
```php
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    // Get a setting value by key
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    // Set a setting value
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    // Get all settings as key => value array
    public static function getAllAsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}
```

---

## ⚙️ Service Classes

### NotificationService.php
```php
class NotificationService
{
    public function sendRenewalReminder(Subscription $subscription, int $daysLeft): void
    {
        $client = $subscription->project->client;

        Mail::to($client->email)->send(
            new SubscriptionReminderMail($subscription, $client, $daysLeft)
        );
    }

    public function sendInvoice(Invoice $invoice): void
    {
        $invoice->load(['client', 'items']);

        Mail::to($invoice->client->email)->send(
            new InvoiceMail($invoice)
        );

        $invoice->update(['status' => 'Sent']);
    }
}
```

### InvoiceNumberService.php
```php
class InvoiceNumberService
{
    public function generate(): string
    {
        $year     = now()->year;
        $count    = Invoice::whereYear('created_at', $year)->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return "INV-{$year}-{$sequence}";
    }
}
```

### InvoicePdfService.php
```php
class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->load(['client', 'items']);
        $settings = Setting::getAllAsArray();

        $pdf  = Pdf::loadView('pdf.invoice', compact('invoice', 'settings'))
                   ->setPaper('a4', 'portrait');

        $path = "invoices/{$invoice->invoice_number}.pdf";
        Storage::put("public/{$path}", $pdf->output());
        $invoice->update(['pdf_path' => $path]);

        return $path;
    }
}
```

---

## 🎮 Livewire Components

### Dashboard/OverviewDashboard.php
```php
class OverviewDashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard.overview-dashboard', [
            'stats' => [
                'critical'     => Subscription::critical()->count(),
                'warning'      => Subscription::warning()->count(),
                'healthy'      => Subscription::healthy()->count(),
                'unpaid'       => Renewal::where('payment_status', 'Invoiced')->count(),
                'overdue'      => Invoice::where('status', 'Overdue')->count(),
                'total_clients' => Client::count(),
            ],
            'criticalSubscriptions' => Subscription::critical()
                ->with('project.client')
                ->orderBy('expiry_date')
                ->take(10)
                ->get(),
            'warningSubscriptions'  => Subscription::warning()
                ->with('project.client')
                ->orderBy('expiry_date')
                ->take(10)
                ->get(),
        ]);
    }
}
```

### Clients/ClientIndex.php
```php
class ClientIndex extends Component
{
    use WithPagination;

    public string $search     = '';
    public bool   $showModal  = false;
    public ?int   $editingId  = null;
    public bool   $confirmDelete = false;
    public ?int   $deletingId    = null;

    public function updatingSearch(): void { $this->resetPage(); }

    public function edit(int $id): void
    {
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId    = $id;
        $this->confirmDelete = true;
    }

    public function delete(): void
    {
        Client::findOrFail($this->deletingId)->delete();
        $this->confirmDelete = false;
        $this->deletingId    = null;
        session()->flash('success', 'Client deleted.');
    }

    public function render()
    {
        return view('livewire.clients.client-index', [
            'clients' => Client::search($this->search)
                ->withCount('projects')
                ->paginate(15),
        ]);
    }
}
```

> **Agent Note:** Follow this same pattern for `ProjectIndex`, `SubscriptionIndex`, `InvoiceIndex`. Use `WithPagination`, expose `search`, `showModal`, `editingId`, `confirmDelete`, `deletingId`. DRY — same structure, different model.

### Invoices/InvoiceBuilder.php
```php
class InvoiceBuilder extends Component
{
    public ?int $clientId    = null;
    public array $renewalIds = [];

    public function mount(?int $invoiceId = null): void
    {
        // Load existing invoice if editing
    }

    public function getAvailableRenewalsProperty()
    {
        if (! $this->clientId) return collect();

        return Renewal::whereHas('subscription.project', function ($q) {
                    $q->where('client_id', $this->clientId);
                })
                ->whereIn('payment_status', ['Pending'])
                ->whereNull('invoice_id')
                ->with('subscription.project')
                ->get();
    }

    public function createInvoice(
        InvoiceNumberService $numberService,
        InvoicePdfService    $pdfService
    ): void {
        $invoice = Invoice::create([
            'client_id'      => $this->clientId,
            'invoice_number' => $numberService->generate(),
            'issued_date'    => now(),
            'due_date'       => now()->addDays((int) Setting::get('invoice_due_days', 14)),
            'status'         => 'Draft',
        ]);

        foreach ($this->renewalIds as $renewalId) {
            $renewal = Renewal::find($renewalId);
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'renewal_id'  => $renewal->id,
                'description' => "{$renewal->subscription->service_type} Renewal — {$renewal->subscription->domain_name}",
                'period'      => $renewal->due_date->format('Y') . ' – ' . $renewal->due_date->addYear()->format('Y'),
                'amount_usd'  => $renewal->client_cost_usd,
            ]);
            $renewal->update(['invoice_id' => $invoice->id, 'payment_status' => 'Invoiced']);
        }

        $invoice->recalculateTotals();
        $pdfService->generate($invoice);

        $this->redirect(route('invoices.show', $invoice));
    }
}
```

---

## 📅 Scheduler

### routes/console.php
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('subscriptions:check-expiries')->dailyAt('08:00');
```

### Console/Commands/CheckSubscriptionExpiries.php
```php
class CheckSubscriptionExpiries extends Command
{
    protected $signature   = 'subscriptions:check-expiries';
    protected $description = 'Send renewal reminders at 30, 14, 7 days and update statuses';

    public function handle(NotificationService $notifier): void
    {
        // Send reminders
        foreach ([30, 14, 7] as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            Subscription::with('project.client')
                ->where('expiry_date', $targetDate)
                ->where('status', '!=', 'Cancelled')
                ->each(fn($sub) => $notifier->sendRenewalReminder($sub, $days));
        }

        // Update statuses
        Subscription::where('expiry_date', '<', now())
            ->whereNotIn('status', ['Expired', 'Cancelled'])
            ->update(['status' => 'Expired']);

        Subscription::whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->where('status', 'Active')
            ->update(['status' => 'Expiring']);

        $this->info('Subscription check complete.');
    }
}
```

---

## 📧 Email Templates

### Mail/SubscriptionReminderMail.php
```php
class SubscriptionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Client       $client,
        public int          $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        $urgency = $this->daysLeft <= 7 ? '🚨 URGENT: ' : '⏳ Reminder: ';
        return new Envelope(
            subject: "{$urgency}Your {$this->subscription->service_type} renewal is due in {$this->daysLeft} days",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.subscription-reminder');
    }
}
```

### resources/views/mail/subscription-reminder.blade.php
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; margin-bottom: 24px; }
        .details-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 24px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 4px 0; }
        .label { color: #6b7280; font-size: 14px; }
        .value { font-weight: 600; }
        .footer { border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 32px; font-size: 13px; color: #6b7280; }
        .urgent { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        @if(Setting::get('logo_url'))
            <img src="{{ Setting::get('logo_url') }}" alt="{{ Setting::get('business_name') }}" height="40">
        @else
            <strong>{{ Setting::get('business_name') }}</strong>
        @endif
    </div>

    <p>Dear {{ $client->name }},</p>

    <p>
        @if($daysLeft <= 7)
            <span class="urgent">This is an urgent reminder.</span>
        @endif
        Your <strong>{{ $subscription->service_type }}</strong> for the
        <strong>{{ $subscription->project->project_name }}</strong> project is due for renewal
        in <strong>{{ $daysLeft }} day{{ $daysLeft !== 1 ? 's' : '' }}</strong>.
    </p>

    <div class="details-box">
        <div class="detail-row">
            <span class="label">Service</span>
            <span class="value">{{ $subscription->service_type }}</span>
        </div>
        @if($subscription->domain_name)
        <div class="detail-row">
            <span class="label">Domain / Service</span>
            <span class="value">{{ $subscription->domain_name }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="label">Project</span>
            <span class="value">{{ $subscription->project->project_name }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Expiry Date</span>
            <span class="value">{{ $subscription->expiry_date->format('d M Y') }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Renewal Cost</span>
            <span class="value">${{ number_format($subscription->renewal_cost_usd, 2) }}</span>
        </div>
    </div>

    <p>
        To keep your website and services running without interruption, please let us know
        if you'd like to proceed with the renewal or if you have any questions.
    </p>

    <p>Best regards,</p>

    <div class="footer">
        <strong>{{ Setting::get('sender_name') }}</strong><br>
        {{ Setting::get('sender_title') }} | {{ Setting::get('business_name') }}<br>
        {{ Setting::get('business_email') }} | {{ Setting::get('business_phone') }}<br>
        <a href="{{ Setting::get('business_website') }}">{{ Setting::get('business_website') }}</a>
    </div>
</div>
</body>
</html>
```

---

## 🧾 PDF Invoice Template

### resources/views/pdf/invoice.blade.php
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1f2937; }
        .page { padding: 40px; }

        /* Header */
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .logo img { max-height: 50px; }
        .business-info { text-align: right; font-size: 12px; color: #6b7280; line-height: 1.8; }
        .business-name { font-size: 16px; font-weight: bold; color: #111827; }

        /* Invoice meta */
        .invoice-meta { display: flex; justify-content: space-between; margin-bottom: 32px; }
        .invoice-title { font-size: 28px; font-weight: 700; color: #111827; letter-spacing: -0.5px; }
        .invoice-number { font-size: 14px; color: #6b7280; margin-top: 4px; }
        .invoice-dates { text-align: right; font-size: 12px; }
        .invoice-dates strong { display: block; font-size: 13px; color: #111827; }

        /* Bill to */
        .bill-to { margin-bottom: 32px; }
        .section-label { font-size: 11px; font-weight: 600; text-transform: uppercase;
                         letter-spacing: 1px; color: #9ca3af; margin-bottom: 6px; }
        .client-name { font-size: 15px; font-weight: 600; }
        .client-detail { font-size: 12px; color: #6b7280; }

        /* Line items table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead tr { background: #1f2937; color: #fff; }
        thead th { padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 600; }
        thead th.amount { text-align: right; }
        tbody tr { border-bottom: 1px solid #f3f4f6; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody td { padding: 10px 12px; font-size: 13px; }
        tbody td.amount { text-align: right; font-weight: 500; }
        .period { font-size: 11px; color: #9ca3af; }

        /* Totals */
        .totals { width: 280px; margin-left: auto; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .total-row.grand { font-size: 16px; font-weight: 700; border-top: 2px solid #1f2937; padding-top: 10px; margin-top: 4px; }

        /* Payment info */
        .payment-section { margin-top: 40px; padding: 20px; border: 1px solid #e5e7eb; border-radius: 6px; }
        .payment-title { font-weight: 600; margin-bottom: 12px; }
        .payment-method { margin-bottom: 8px; }
        .payment-label { font-size: 11px; text-transform: uppercase; color: #9ca3af; font-weight: 600; }
        .payment-value { font-size: 13px; }

        /* Footer */
        .invoice-footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="invoice-header">
        <div class="logo">
            @if(!empty($settings['logo_url']))
                <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['business_name'] }}">
            @else
                <span style="font-size:20px; font-weight:700;">{{ $settings['business_name'] ?? 'Your Business' }}</span>
            @endif
        </div>
        <div class="business-info">
            <div class="business-name">{{ $settings['business_name'] ?? '' }}</div>
            {{ $settings['business_website'] ?? '' }}<br>
            {{ $settings['business_email'] ?? '' }}<br>
            {{ $settings['business_phone'] ?? '' }}
        </div>
    </div>

    {{-- Invoice Meta --}}
    <div class="invoice-meta">
        <div>
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
        </div>
        <div class="invoice-dates">
            <span class="section-label">Issue Date</span>
            <strong>{{ $invoice->issued_date->format('d M Y') }}</strong>
            <span class="section-label" style="margin-top:8px;">Due Date</span>
            <strong>{{ $invoice->due_date->format('d M Y') }}</strong>
        </div>
    </div>

    {{-- Bill To --}}
    <div class="bill-to">
        <div class="section-label">Bill To</div>
        <div class="client-name">{{ $invoice->client->name }}</div>
        @if($invoice->client->company_name)
            <div class="client-detail">{{ $invoice->client->company_name }}</div>
        @endif
        <div class="client-detail">{{ $invoice->client->email }}</div>
    </div>

    {{-- Line Items --}}
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Period</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="period">{{ $item->period }}</td>
                <td class="amount">${{ number_format($item->amount_usd, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <div class="total-row">
            <span>Subtotal</span>
            <span>${{ number_format($invoice->subtotal_usd, 2) }}</span>
        </div>
        <div class="total-row grand">
            <span>Total Due (USD)</span>
            <span>${{ number_format($invoice->total_usd, 2) }}</span>
        </div>
    </div>

    {{-- Payment Info --}}
    <div class="payment-section">
        <div class="payment-title">Payment Instructions</div>
        @if(!empty($settings['bank_name']))
        <div class="payment-method">
            <div class="payment-label">Bank Transfer</div>
            <div class="payment-value">
                {{ $settings['bank_name'] }} — {{ $settings['bank_account_name'] }}<br>
                Account: {{ $settings['bank_account_number'] }}
            </div>
        </div>
        @endif
        @if(!empty($settings['paypal_email']))
        <div class="payment-method">
            <div class="payment-label">PayPal</div>
            <div class="payment-value">{{ $settings['paypal_email'] }}</div>
        </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="invoice-footer">
        Payment due within {{ $settings['invoice_due_days'] ?? 14 }} days of invoice date.
        Thank you for your business — {{ $settings['business_name'] ?? '' }}
    </div>

</div>
</body>
</html>
```

---

## 🛣️ Routes

### routes/web.php
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard',          OverviewDashboard::class)->name('dashboard');
    Route::get('/clients',            ClientIndex::class)->name('clients.index');
    Route::get('/projects',           ProjectIndex::class)->name('projects.index');
    Route::get('/subscriptions',      SubscriptionIndex::class)->name('subscriptions.index');
    Route::get('/renewals',           RenewalTracker::class)->name('renewals.index');
    Route::get('/invoices',           InvoiceIndex::class)->name('invoices.index');
    Route::get('/invoices/create',    InvoiceBuilder::class)->name('invoices.create');
    Route::get('/invoices/{invoice}', InvoiceBuilder::class)->name('invoices.show');
    Route::get('/settings',           AppSettings::class)->name('settings.index');
});
```

---

## ⚗️ Settings Keys Reference

Seed these defaults into the settings table via a seeder:

```php
$defaults = [
    'business_name'       => '',
    'business_email'      => '',
    'business_phone'      => '',
    'business_website'    => '',
    'logo_url'            => '',
    'bank_name'           => '',
    'bank_account_name'   => '',
    'bank_account_number' => '',
    'paypal_email'        => '',
    'invoice_due_days'    => '14',
    'sender_name'         => '',
    'sender_title'        => '',
    'app_name'            => 'SubTrack',
    'reminder_days'       => '30,14,7',
];

foreach ($defaults as $key => $value) {
    Setting::firstOrCreate(['key' => $key], ['value' => $value]);
}
```

---

## 🏃 Agile Build Phases

Track your progress by checking off tasks. Do not start a phase until the previous one is fully complete and tested.

---

### ✅ Phase 1 — Foundation
**Goal:** App runs, auth works, database is ready.

- [ ] Install Laravel 12, Breeze (Blade), Livewire 3
- [ ] Install FlyonUI, configure Tailwind
- [ ] Install `codeat3/blade-tabler-icons`
- [ ] Install `barryvdh/laravel-dompdf`
- [ ] Create all 7 migrations
- [ ] Run migrations
- [ ] Create all 6 Models with relationships and scopes
- [ ] Create 3 Service classes (stub methods ok)
- [ ] Seed default Settings
- [ ] Verify auth login/logout works
- [ ] Create `layouts/app.blade.php` (sidebar + topbar layout)
- [ ] Create `nav/sidebar.blade.php` with all nav links
- [ ] Create `nav/topbar.blade.php` with page title slot

**Phase 1 complete when:** You can log in and see the sidebar layout on a blank dashboard.

---

### ✅ Phase 2 — Core UI Components
**Goal:** All reusable Blade components are built BEFORE any feature pages. This enforces DRY from the start.

> **Critical Agent Instruction:** Every component below MUST be built as a Blade anonymous component in `resources/views/components/ui/`. Feature pages will ONLY use these components. If a pattern isn't here, add a new component — never write one-off HTML in feature views.

- [ ] `<x-ui.page-header>` — title, subtitle, optional action button slot
- [ ] `<x-ui.stat-card>` — icon, label, value, color variant (critical/warning/healthy/neutral)
- [ ] `<x-ui.badge-status>` — subscription status (Active/Expiring/Expired/Cancelled) → mapped colors
- [ ] `<x-ui.badge-payment>` — payment status (Pending/Invoiced/Paid/Renewed/Lapsed) → mapped colors
- [ ] `<x-ui.data-table>` — accepts `$headers` array + default slot for tbody rows
- [ ] `<x-ui.empty-state>` — icon, title, message, optional action button
- [ ] `<x-ui.confirm-modal>` — Alpine.js modal, title, message, confirm/cancel buttons
- [ ] `<x-ui.form-input>` — label, wire:model, error display, optional prefix/suffix
- [ ] `<x-ui.form-select>` — label, wire:model, options array, error display
- [ ] `<x-ui.form-textarea>` — label, wire:model, rows, error display
- [ ] `<x-ui.action-menu>` — dropdown with Edit / Delete actions (Tabler icons)

**Phase 2 complete when:** All components render correctly in isolation. Build a `/components-preview` dev-only route to test them all together.

---

### ✅ Phase 3 — Settings
**Goal:** Business info, payment details, and email config are all configurable before anything sends emails.

- [ ] `AppSettings` Livewire component — grouped sections (Business, Payment, Email, Notifications)
- [ ] File upload for logo (store in `public/storage/logo/`)
- [ ] Save/update all settings keys
- [ ] Flash success message on save
- [ ] Test: change a setting and verify `Setting::get()` returns it

**Phase 3 complete when:** All settings save and persist correctly.

---

### ✅ Phase 4 — Client & Project Management
**Goal:** Full CRUD for Clients and Projects.

- [ ] `ClientIndex` Livewire — list, search, paginate, delete with confirm modal
- [ ] `ClientForm` Livewire — create and edit in a slide-over or modal panel
- [ ] Validation: name required, email required + unique (ignoring self on edit)
- [ ] `ProjectIndex` Livewire — list with client name, filter by client
- [ ] `ProjectForm` Livewire — create and edit, client dropdown
- [ ] Test: create client → create project under client → verify relationship

**Phase 4 complete when:** Clients and Projects are fully operational.

---

### ✅ Phase 5 — Subscription Tracking
**Goal:** Full CRUD for subscriptions with traffic light indicators.

- [ ] `SubscriptionIndex` Livewire — list, search by client/project/service, filter by status
- [ ] `SubscriptionForm` Livewire — create and edit, service type enum, dates, costs
- [ ] Traffic light badge using `$subscription->traffic_light` attribute
- [ ] Days until expiry shown on each row
- [ ] Dashboard stats wired up (critical/warning/healthy counts)
- [ ] Dashboard shows top 10 critical and warning subscriptions

**Phase 5 complete when:** Dashboard correctly reflects subscription statuses.

---

### ✅ Phase 6 — Scheduler & Email Reminders
**Goal:** Automated reminders fire on schedule and emails look professional.

- [ ] `CheckSubscriptionExpiries` command — reminders at 30, 14, 7 days + status updates
- [ ] Register command in `routes/console.php` scheduler
- [ ] `SubscriptionReminderMail` mailable
- [ ] `subscription-reminder.blade.php` email view (using Settings for dynamic footer)
- [ ] Test: manually run `php artisan subscriptions:check-expiries` and verify emails send
- [ ] Test: verify status updates (Active → Expiring → Expired) work correctly

**Phase 6 complete when:** You can manually trigger the command and receive a correctly formatted email.

---

### ✅ Phase 7 — Renewal Tracker
**Goal:** Track the full payment lifecycle for each renewal.

- [ ] `RenewalTracker` Livewire — list all pending renewals, group by client
- [ ] Filter by payment status
- [ ] Inline status update (click to change status)
- [ ] Record payment received date when marking as Paid
- [ ] Record renewal confirmed date when marking as Renewed
- [ ] Auto-update parent Subscription `expiry_date` + 1 year when marked Renewed
- [ ] Notes field for each renewal

**Phase 7 complete when:** Full renewal lifecycle (Pending → Invoiced → Paid → Renewed) can be tracked.

---

### ✅ Phase 8 — Invoice Generation
**Goal:** Generate professional PDF invoices and email them to clients.

- [ ] `InvoiceBuilder` Livewire — select client, pick pending renewals to bundle
- [ ] Generate invoice with auto-incremented invoice number
- [ ] `InvoicePdfService` — generate PDF to `storage/app/public/invoices/`
- [ ] Download PDF button
- [ ] `InvoiceMail` mailable — attach PDF to email
- [ ] Send invoice via email button
- [ ] `InvoiceIndex` Livewire — list all invoices, filter by status, show totals
- [ ] Mark invoice as Paid → also marks bundled renewals as Paid
- [ ] Overdue detection: invoices past due_date with status Sent → auto flag Overdue

**Phase 8 complete when:** You can create an invoice, download the PDF, email it to a client, and mark it paid.

---

### ✅ Phase 9 — Polish & QA
**Goal:** App is production-ready.

- [ ] 404 and error pages styled consistently
- [ ] All flash messages use consistent component
- [ ] Mobile responsiveness check (sidebar collapses)
- [ ] Form validation error messages on all forms
- [ ] Soft delete — verify deleted clients don't show up
- [ ] Empty states on all list pages using `<x-ui.empty-state>`
- [ ] `.env` review — no hardcoded values
- [ ] Storage link: `php artisan storage:link`
- [ ] Final cron entry documented in README

**Phase 9 complete when:** App is deployable and all flows tested end-to-end.

---

## 📦 Package Installation Summary

```bash
# PHP packages
composer require barryvdh/laravel-dompdf
composer require codeat3/blade-tabler-icons

# Frontend
npm install flyonui
npm install -D tailwindcss @tailwindcss/forms

# Breeze (run first, before other packages)
php artisan breeze:install blade
```

### tailwind.config.js additions
```js
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './node_modules/flyonui/dist/js/*.js',
    ],
    plugins: [
        require('flyonui'),
        require('flyonui/plugin'),
    ],
}
```

---

## 🚀 Server Cron (Production)

Add this single line to your server crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

*End of SPEC.md*
