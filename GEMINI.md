# ⚡ SESSION PROTOCOL — READ THIS FIRST, EVERY TIME

## Step 1 — Orient yourself (do this before anything else)

1. Read `PROGRESS.md` — this tells you exactly where the project is
2. Identify the **Current Status** section — that is your starting point
3. Find the **Next Task** — that is what you work on

## Step 2 — Only read other files when you need them

| When | Read |
|---|---|
| Starting a brand new phase | The relevant phase section in `SPEC.md` |
| Writing any UI / Blade file | `DESIGN_SYSTEM.md` |
| Unsure about a rule | `GEMINI.md` (architecture rules section) |
| Resuming mid-phase | `PROGRESS.md` only — no other file needed |

**Do not re-read files you have already read in this session.**
**Do not re-read SPEC.md or DESIGN_SYSTEM.md just to orient yourself — PROGRESS.md is enough.**

## Step 3 — Work on the Next Task only

- Complete one task at a time
- Tick it off in `PROGRESS.md` immediately after completing it
- Update **Current Status → Next Task** to the following task
- If you encounter a blocker, log it in the **Known Issues** table

## Step 4 — End of session update (mandatory)

Before ending ANY session, update `PROGRESS.md`:

```
1. Tick every completed task checkbox
2. Update "Active Phase" if you moved to a new phase
3. Update "Last Completed Task" with the exact task name
4. Update "Next Task" with what comes next
5. Add every new file path to the "Files Created" list for the current phase
6. Add a row to the Session Log table
7. Log any bugs or deviations in Known Issues
```

**Never end a session without updating PROGRESS.md.**
**This is how the next session knows where to start without wasting tokens re-reading everything.**

---
# Agent Configuration

## Required Reading
Before writing any code, read these files in this exact order:
1. Read GEMINI.md (this file) — rules and standards
2. Read SPEC.md — full technical specification and phase plan
3. Read DESIGN_SYSTEM.md — UI components, colors, typography

Do not begin Phase 1 until all three files have been read.
Confirm reading by listing the 9 phases from SPEC.md before starting.
```

**Step 4 — Open Antigravity, open the `subtrack/` folder as your workspace**

Once a workspace is selected, Antigravity automatically prepares the Agent Manager to start a new conversation scoped to that folder.  This means it will have access to all three files automatically.

**Step 5 — In the Agent Manager, send this as your first message:**
```
Read GEMINI.md, SPEC.md, and DESIGN_SYSTEM.md in full.
Confirm you've read them by listing:
1. The 9 build phases from SPEC.md
2. The 4 Enum classes you need to create
3. The UI component library location

Then begin Phase 1 only.

# AGENT_RULES.md — Development Standards & Architecture Rules

> **Agent Instruction:** Read this file in full before writing a single line of code.
> These rules are non-negotiable. They exist to enforce consistency, scalability, and
> readability across the entire codebase. When in doubt, re-read this file.

---

## 🧠 Agent Behaviour Rules

1. **Read before you write.** Always read `SPEC.md` and `DESIGN_SYSTEM.md` before starting any phase.
2. **One phase at a time.** Complete the current phase fully. List every file created. Wait for confirmation before the next phase.
3. **No assumptions.** If a requirement is ambiguous, stop and ask. Do not invent requirements.
4. **No placeholders.** Never write `// TODO`, `// implement later`, or stub logic in final output. Either implement it or flag it explicitly.
5. **No silent changes.** If you deviate from the spec for a technical reason, state the reason clearly before writing the code.
6. **No hallucinated packages.** Only use packages listed in `SPEC.md`. Do not introduce new dependencies without flagging them.
7. **Self-review before output.** Before presenting code, mentally check: Does this follow every rule in this file?

---

## 📐 Universal Development Principles

These apply to all languages, all frameworks, all projects.

### DRY — Don't Repeat Yourself
- If you write the same logic twice, extract it. A function, a class, a component — your choice. Never copy-paste logic.
- Duplication is a future bug waiting to happen.

### KISS — Keep It Simple, Stupid
- The simplest solution that works is the correct solution.
- Complexity must be justified. If you can't explain why it's complex, simplify it.
- Clever code is hard to debug. Clear code is always better than clever code.

### YAGNI — You Aren't Gonna Need It
- Do not build features that aren't in the spec.
- Do not add abstractions for hypothetical future requirements.
- Build for now. Refactor when the need is real.

### SOLID Principles (enforced at all times)
| Principle | What it means in practice |
|---|---|
| **S** — Single Responsibility | One class does one thing. A Controller that sends emails is wrong. A Service sends emails. |
| **O** — Open/Closed | Classes are open for extension, closed for modification. Use inheritance and interfaces, not `if/else` chains. |
| **L** — Liskov Substitution | Subclasses must be substitutable for their parent. Don't override behaviour in ways that break expectations. |
| **I** — Interface Segregation | Don't force a class to implement methods it doesn't need. Small, focused interfaces over large ones. |
| **D** — Dependency Inversion | Depend on abstractions, not concretions. Inject dependencies, don't instantiate them inside a class. |

### Separation of Concerns
- **Controllers / Livewire Components:** Handle HTTP or UI events only. No business logic.
- **Service Classes:** All business logic lives here.
- **Models:** Data access, relationships, scopes, accessors. No business logic.
- **Blade / Views:** Presentation only. No PHP logic beyond simple conditionals and loops.

---

## 🏛️ Laravel Architecture Rules

### ✅ Enums — Never Use Inline DB Enums

**Wrong:**
```php
// In migration
$table->enum('status', ['Active', 'Expiring', 'Expired', 'Cancelled']);
```

**Right:**
```php
// 1. Create a PHP Enum class
// app/Enums/SubscriptionStatus.php
namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active    = 'Active';
    case Expiring  = 'Expiring';
    case Expired   = 'Expired';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Active',
            self::Expiring  => 'Expiring Soon',
            self::Expired   => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Active    => 'success',
            self::Expiring  => 'warning',
            self::Expired   => 'error',
            self::Cancelled => 'neutral',
        };
    }
}

// 2. In migration — use string, not enum
$table->string('status')->default(SubscriptionStatus::Active->value);

// 3. Cast in Model
protected $casts = [
    'status' => SubscriptionStatus::class,
];

// 4. Usage in code — never use raw strings
$subscription->status === SubscriptionStatus::Active;
$subscription->update(['status' => SubscriptionStatus::Expired]);
```

**Why:** Database-level enums are a migration nightmare. Adding or renaming a value requires an `ALTER TABLE` on potentially large tables. String columns with PHP Enum casting give you type safety, IDE autocomplete, and zero migration pain.

**All enums for this project:**
```
app/Enums/
├── SubscriptionStatus.php   — Active, Expiring, Expired, Cancelled
├── ServiceType.php          — Domain, Hosting, SSL, Maintenance, Other
├── PaymentStatus.php        — Pending, Invoiced, Paid, Renewed, Lapsed
└── InvoiceStatus.php        — Draft, Sent, Paid, Overdue
```

---

### ✅ Models — Rules

```php
// CORRECT — Model structure order
class Subscription extends Model
{
    // 1. Traits
    use HasFactory, SoftDeletes;

    // 2. Fillable (explicit — never use $guarded = [])
    protected $fillable = [...];

    // 3. Casts (always cast dates, enums, booleans, JSON)
    protected $casts = [
        'expiry_date' => 'date',
        'status'      => SubscriptionStatus::class,
    ];

    // 4. Relationships (alphabetical order)
    public function project(): BelongsTo { ... }
    public function renewals(): HasMany { ... }

    // 5. Scopes (prefix with scope, no other prefix)
    public function scopeCritical(Builder $query): Builder { ... }

    // 6. Accessors / Mutators (new Laravel syntax)
    public function daysUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: fn() => now()->diffInDays($this->expiry_date, false)
        );
    }

    // 7. Regular methods (business-adjacent, not business logic)
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }
}
```

**Never:**
- Business logic in a Model method (that's a Service's job)
- Raw queries in a Model that could be a scope
- `$guarded = []` — always be explicit with `$fillable`
- Accessing relationships without eager loading in loops (N+1)

---

### ✅ Controllers & Livewire Components — Rules

**Controllers (if used for non-Livewire routes like PDF download):**
```php
// Max 5 lines per method. If it's longer, extract to a Service.
public function download(Invoice $invoice, InvoicePdfService $pdf): Response
{
    $path = $pdf->generate($invoice);
    return Storage::download($path);
}
```

**Livewire Components:**
```php
// CORRECT structure
class ClientIndex extends Component
{
    // 1. Public properties (bound to view)
    public string $search = '';
    public bool $showModal = false;

    // 2. Computed properties (use #[Computed] attribute)
    #[Computed]
    public function clients()
    {
        return Client::search($this->search)->paginate(15);
    }

    // 3. Lifecycle hooks
    public function mount(): void { ... }

    // 4. Event handlers (actions)
    public function openCreate(): void { ... }
    public function save(): void { ... }
    public function delete(int $id): void { ... }

    // 5. render() always last
    public function render(): View
    {
        return view('livewire.clients.client-index');
    }
}
```

**Never:**
- DB queries directly in a Livewire component — use a Model scope or Service
- Business logic in a Livewire component — delegate to a Service class
- More than 150 lines in a single Livewire component — split it

---

### ✅ Service Classes — Rules

```php
// app/Services/NotificationService.php
class NotificationService
{
    // Constructor injection only — never instantiate dependencies inside methods
    public function __construct(
        private readonly InvoicePdfService $pdfService,
    ) {}

    // One method = one action. Name it with a verb.
    public function sendRenewalReminder(Subscription $subscription, int $daysLeft): void
    {
        // All logic here. No DB calls that belong in a scope.
        // No presentation logic. No Blade knowledge.
    }
}
```

**Rules:**
- Always use constructor injection, never `new ClassName()` inside a method
- Method names are verbs: `sendReminder()`, `generatePdf()`, `markAsPaid()`
- Services are stateless — no stored state between method calls
- If a service grows beyond 200 lines, split it into focused sub-services

---

### ✅ Database & Migration Rules

```php
// CORRECT migration structure
public function up(): void
{
    Schema::create('subscriptions', function (Blueprint $table) {
        // 1. Primary key (always first)
        $table->id();

        // 2. Foreign keys (immediately after id)
        $table->foreignId('project_id')->constrained()->cascadeOnDelete();

        // 3. String columns — always specify length if not 255
        $table->string('provider', 100);
        $table->string('domain_name', 255)->nullable();

        // 4. Enum replacement — always string
        $table->string('service_type', 50);
        $table->string('status', 50)->default('Active');

        // 5. Numeric — always specify precision
        $table->decimal('renewal_cost_usd', 10, 2);

        // 6. Dates
        $table->date('expiry_date');

        // 7. Nullable fields grouped together
        $table->text('notes')->nullable();

        // 8. Timestamps always last
        $table->timestamps();
        $table->softDeletes();
    });
}

// Always implement down()
public function down(): void
{
    Schema::dropIfExists('subscriptions');
}
```

**Rules:**
- Never use `->enum()` — use `->string()` with a PHP Enum class
- Always define `down()` — migrations must be reversible
- Foreign keys always use `constrained()` — let the DB enforce integrity
- Always add `softDeletes()` to main entity tables (Client, Project, Subscription, Invoice)
- Index columns you filter or sort by: `$table->index('expiry_date')`
- Decimal columns for money — never `float` (floating point precision errors)

---

### ✅ Query Rules — Prevent N+1

```php
// WRONG — N+1 query problem
$subscriptions = Subscription::all();
foreach ($subscriptions as $sub) {
    echo $sub->project->client->name; // 1 query per iteration
}

// CORRECT — eager load everything you'll use
$subscriptions = Subscription::with('project.client')->get();

// CORRECT — in Livewire computed properties
#[Computed]
public function subscriptions()
{
    return Subscription::with('project.client')
        ->critical()
        ->orderBy('expiry_date')
        ->paginate(15);
}
```

**Rule:** Before any loop over a collection, ask: "Will I access a relationship inside this loop?" If yes, eager load it with `with()`.

---

### ✅ Validation Rules

```php
// CORRECT — always use Form Request classes for non-trivial validation
// app/Http/Requests/StoreClientRequest.php
class StoreClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', Rule::unique('clients')->ignore($this->client)],
            'phone'        => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}

// In Livewire — use #[Validate] attribute or validate() method
#[Validate([
    'name'  => 'required|string|max:255',
    'email' => 'required|email',
])]
public string $name = '';
public string $email = '';
```

**Never:**
- Validate in a Controller or Livewire component inline with arrays when a Form Request is cleaner
- Skip validation on any user input
- Trust client-side validation alone

---

### ✅ Naming Conventions

| Thing | Convention | Example |
|---|---|---|
| Model | Singular PascalCase | `Client`, `InvoiceItem` |
| Table | Plural snake_case | `clients`, `invoice_items` |
| Controller | Singular + Controller | `ClientController` |
| Livewire Component | PascalCase | `ClientIndex`, `InvoiceBuilder` |
| Service | Noun + Service | `InvoicePdfService` |
| Enum | Noun + context | `SubscriptionStatus`, `ServiceType` |
| Migration | timestamp_verb_noun | `2025_01_01_create_clients_table` |
| Blade view | kebab-case | `client-index.blade.php` |
| Blade component | kebab-case | `<x-ui.stat-card>` |
| Route name | resource.action | `clients.index`, `invoices.create` |
| CSS class (custom) | kebab-case | `.stat-card`, `.badge-status` |
| JS variable | camelCase | `clientName`, `isModalOpen` |
| Method name | camelCase verb | `sendReminder()`, `generatePdf()` |

---

### ✅ Route Rules

```php
// CORRECT — named routes, grouped by middleware
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', OverviewDashboard::class)->name('dashboard');

    // Resource-style grouping
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/',          ClientIndex::class)->name('index');
        Route::get('/create',    ClientForm::class)->name('create');
        Route::get('/{client}',  ClientForm::class)->name('edit');
    });
});
```

**Rules:**
- Always name routes — never use raw URLs in code or Blade
- Use `route('name')` in Blade, never hardcoded `/path`
- Group related routes with prefix + name prefix
- Route parameters use model binding: `/{client}` not `/{id}`

---

### ✅ Config & Environment Rules

```php
// WRONG — never hardcode environment values
$mail = 'smtp.gmail.com';

// CORRECT — always use config() or env() properly
// .env
MAIL_HOST=smtp.gmail.com

// config/mail.php (already exists in Laravel)
'host' => env('MAIL_HOST', 'smtp.mailgun.org'),

// In code — always use config(), never env() directly
Mail::to(config('mail.from.address'));
```

**Rules:**
- `env()` is only called inside `config/` files — never in app code
- All configurable values are in `.env` with defaults in `config/`
- Never commit `.env` — only commit `.env.example`

---

## 🎨 UI & Frontend Rules

### ✅ Blade Component Rules

```blade
{{-- WRONG — inline one-off HTML --}}
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
    Active
</span>

{{-- CORRECT — always use a component --}}
<x-ui.badge-status :status="$subscription->status->value" />
```

**Rules:**
- If a UI pattern appears more than once anywhere in the app, it is a component. No exceptions.
- Components live in `resources/views/components/ui/`
- Components accept props via `@props([])` — never read variables from the outer scope
- Components are documented with a usage example comment at the top of the file

---

### ✅ Tailwind & FlyonUI Rules

```blade
{{-- WRONG — arbitrary values, magic numbers --}}
<div class="p-[13px] mt-[22px] text-[#3b82f6]">

{{-- CORRECT — use design tokens and Tailwind scale --}}
<div class="p-3 mt-5 text-blue-500">

{{-- WRONG — hardcoded colors outside the palette --}}
<div class="bg-[#ff5733]">

{{-- CORRECT — only use colors from the design token palette --}}
<div class="bg-red-500">
```

**Rules:**
- Never use arbitrary Tailwind values `[]` unless absolutely unavoidable — flag it if you do
- Never use inline `style=""` attributes — use Tailwind classes or CSS variables
- Only use colors defined in `DESIGN_SYSTEM.md` — never invent new colors
- FlyonUI component classes always take precedence over custom Tailwind for interactive elements (buttons, modals, badges, inputs)
- Responsive classes order: mobile-first, then `sm:`, `md:`, `lg:`, `xl:`

---

### ✅ Alpine.js Rules

```blade
{{-- WRONG — Alpine managing state that Livewire owns --}}
<div x-data="{ status: '{{ $subscription->status }}' }">

{{-- CORRECT — Alpine handles UI-only state (open/close, toggles) --}}
{{-- Livewire handles all data and server state --}}
<div x-data="{ isOpen: false }">
    <button @click="isOpen = !isOpen">Toggle</button>
    <div x-show="isOpen">...</div>
</div>
```

**Rule:** Alpine.js is for UI-only behaviour (show/hide, toggles, animations). Livewire owns all data, server calls, and application state. Never duplicate state between them.

---

### ✅ Livewire View Rules

```blade
{{-- CORRECT structure for every Livewire view --}}

{{-- 1. Always use the app layout --}}
<div>  {{-- Livewire requires a single root element --}}

    {{-- 2. Page header component --}}
    <x-ui.page-header title="Clients" subtitle="Manage your client accounts">
        <button wire:click="openCreate" class="btn btn-primary btn-sm">
            <x-tabler-plus class="w-4 h-4 mr-1" /> Add Client
        </button>
    </x-ui.page-header>

    {{-- 3. Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- 4. Main content --}}
    @if($this->clients->isEmpty())
        <x-ui.empty-state
            icon="users"
            title="No clients yet"
            message="Add your first client to get started."
        />
    @else
        <x-ui.data-table :headers="['Name', 'Email', 'Projects', '']">
            @foreach($this->clients as $client)
                {{-- table rows --}}
            @endforeach
        </x-ui.data-table>
        {{ $this->clients->links() }}
    @endif

    {{-- 5. Modals always at the bottom --}}
    <x-ui.confirm-modal
        title="Delete Client"
        message="This will also delete all their projects and subscriptions."
        confirmAction="delete"
    />

</div>
```

---

### ✅ Icon Rules

- Use only **Tabler Icons** via `<x-tabler-{icon-name}>`
- Always size icons explicitly: `class="w-4 h-4"` (small), `class="w-5 h-5"` (medium), `class="w-6 h-6"` (large)
- Never use an icon without a label nearby (for accessibility) unless it has a `title` attribute
- Do not mix icon sets — no Heroicons, no FontAwesome

---

### ✅ Loading States

```blade
{{-- Every button that triggers a server call must show a loading state --}}
<button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
    <span wire:loading.remove>Save</span>
    <span wire:loading>
        <span class="loading loading-spinner loading-xs"></span> Saving...
    </span>
</button>

{{-- Tables should show a loading overlay when filtering/searching --}}
<div wire:loading.class="opacity-50 pointer-events-none">
    <x-ui.data-table ...>
```

**Rule:** Every user action that calls the server must have visual feedback. No silent loading.

---

### ✅ Empty State Rules

- Every list page (Clients, Projects, Subscriptions, Invoices, Renewals) **must** have an empty state
- Use `<x-ui.empty-state>` component — never show a blank page or empty table
- Empty state must include: icon, title, helpful message, and a call-to-action button where relevant

---

### ✅ Accessibility Rules

- All form inputs must have an associated `<label>`
- All icon-only buttons must have `title=""` or `aria-label=""`
- Use semantic HTML: `<nav>`, `<main>`, `<section>`, `<header>`, `<footer>` appropriately
- Color is never the only indicator of meaning (traffic light badges always show text too, not just colour)
- Minimum contrast ratio: 4.5:1 for body text, 3:1 for large text

---

## 🗂️ File Organisation Rules

```
app/
├── Console/Commands/       ← Artisan commands only
├── Enums/                  ← All PHP Enum classes
├── Http/
│   ├── Controllers/        ← Thin controllers (max 5 lines per method)
│   └── Requests/           ← Form Request validation classes
├── Livewire/               ← Livewire components (organised by feature)
├── Mail/                   ← Mailable classes
├── Models/                 ← Eloquent models
└── Services/               ← All business logic
```

**Rules:**
- Features are grouped by noun, not by type. All Client-related Livewire components are in `Livewire/Clients/`, not scattered.
- New feature = new subfolder in `Livewire/`. Never dump everything in the root.
- Service classes are in `app/Services/`. One service per domain concept.

---

## 🔐 Security Rules

- Never trust user input — validate everything server-side
- Always use Laravel's mass assignment protection (`$fillable`)
- Never expose internal IDs in URLs where possible — use model binding with route model binding
- Never store sensitive credentials in the database as plain text
- Email addresses, phone numbers, bank details in Settings are for display only — never use them for authentication
- Use `Storage::url()` not direct file paths in views

---

## ✅ Code Review Checklist

Before submitting any phase, verify every item:

```
ARCHITECTURE
[ ] No business logic in Controllers or Livewire components
[ ] No DB queries in Blade views
[ ] No raw strings where an Enum exists
[ ] All enums are PHP Enum classes, not DB-level enums
[ ] Services injected via constructor, not instantiated inline

DATABASE
[ ] All migrations have a down() method
[ ] No enum() columns — string() used instead
[ ] All date/enum/bool columns have $casts entries in Model
[ ] Eager loading used in all loops with relationships
[ ] Money columns use decimal(10,2), never float

UI
[ ] No duplicate HTML — components used everywhere
[ ] No inline styles
[ ] No arbitrary Tailwind values
[ ] Every list page has an empty state
[ ] Every server-action button has a loading state
[ ] Only Tabler Icons used
[ ] All colors from the design token palette

NAMING
[ ] Models singular, tables plural
[ ] All routes are named
[ ] All methods are verb-named
[ ] Files follow the folder structure in SPEC.md

GENERAL
[ ] No TODO comments in submitted code
[ ] No hardcoded business name/email/payment info
[ ] No env() calls outside config/ files
[ ] No N+1 queries
```

---

*End of AGENT_RULES.md*
