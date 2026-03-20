# SubTrack — Design System & UI Component Specification

---

## 🎨 Aesthetic Direction

**Tone:** Clean, professional, utilitarian — with sharp accents. Think "finance dashboard meets a well-run agency." No decorative flourishes. Every element earns its place.

**Primary personality:** Trustworthy, precise, fast.

---

## 🎨 Color Tokens

Define these as CSS custom properties in your main CSS file AND as Tailwind/FlyonUI config:

```css
:root {
    /* Brand */
    --color-primary:        #0f172a;   /* Slate 900 — nav, headings */
    --color-accent:         #3b82f6;   /* Blue 500 — CTA buttons, links */
    --color-accent-hover:   #2563eb;   /* Blue 600 */

    /* Surface */
    --color-bg:             #f8fafc;   /* Slate 50 — page background */
    --color-surface:        #ffffff;   /* Card, modal, panel backgrounds */
    --color-border:         #e2e8f0;   /* Slate 200 */
    --color-border-strong:  #cbd5e1;   /* Slate 300 */

    /* Text */
    --color-text-primary:   #0f172a;   /* Headings */
    --color-text-secondary: #64748b;   /* Labels, meta */
    --color-text-muted:     #94a3b8;   /* Placeholders, hints */

    /* Traffic Light — Subscriptions */
    --color-critical:       #ef4444;   /* Red 500 — ≤7 days */
    --color-critical-bg:    #fef2f2;   /* Red 50 */
    --color-warning:        #f59e0b;   /* Amber 500 — ≤30 days */
    --color-warning-bg:     #fffbeb;   /* Amber 50 */
    --color-healthy:        #22c55e;   /* Green 500 — >30 days */
    --color-healthy-bg:     #f0fdf4;   /* Green 50 */

    /* Payment Status */
    --color-pending:        #94a3b8;   /* Slate 400 */
    --color-invoiced:       #818cf8;   /* Indigo 400 */
    --color-paid:           #22c55e;   /* Green 500 */
    --color-renewed:        #0ea5e9;   /* Sky 500 */
    --color-lapsed:         #ef4444;   /* Red 500 */

    /* Sidebar */
    --color-sidebar-bg:     #0f172a;   /* Slate 900 */
    --color-sidebar-text:   #94a3b8;   /* Slate 400 */
    --color-sidebar-active: #ffffff;
    --color-sidebar-active-bg: #1e293b; /* Slate 800 */
    --color-sidebar-accent: #3b82f6;   /* Blue 500 — active indicator */
}
```

---

## 🔤 Typography

```css
/* Import in app.css */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

body {
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    line-height: 1.5;
}

.font-mono, code, .invoice-number, .amount {
    font-family: 'DM Mono', monospace;
}
```

### Type Scale
| Use case | Class | Size |
|---|---|---|
| Page title | `text-2xl font-bold` | 24px |
| Section title | `text-lg font-semibold` | 18px |
| Card title | `text-base font-semibold` | 16px |
| Body | `text-sm` | 14px |
| Label / meta | `text-xs text-secondary` | 12px |
| Mono (amounts, codes) | `text-sm font-mono` | 14px |

---

## 📐 Spacing Rules

- Page padding: `p-6` (24px)
- Card padding: `p-5` (20px)
- Section gap: `gap-6` (24px)
- Form field gap: `gap-4` (16px)
- Table cell padding: `px-4 py-3`
- Sidebar width: `w-64` (256px)
- Topbar height: `h-16` (64px)

---

## 🏗️ Layout

### App Shell

```
┌─────────────────────────────────────────────────────┐
│ SIDEBAR (w-64, fixed, full height, dark)             │
│                                                       │
│  [Logo / App Name]                                    │
│  ─────────────                                        │
│  • Dashboard                                          │
│  • Clients                                            │
│  • Projects                                           │
│  • Subscriptions                                      │
│  • Renewals                                           │
│  • Invoices                                           │
│  ─────────────                                        │
│  • Settings                                           │
│                                                       │
├─────────────────────────────────────────────────────┤
│ TOPBAR (h-16, sticky, white, border-bottom)          │
│  Page Title                    [Action Button]        │
├─────────────────────────────────────────────────────┤
│ MAIN CONTENT (flex-1, bg-slate-50, overflow-y-auto)  │
│  <slot />                                             │
└─────────────────────────────────────────────────────┘
```

---

## 🧩 Blade Component Specifications

All components live in `resources/views/components/ui/`.
All components are self-contained — no one-off styles allowed outside of a component.

---

### `<x-ui.page-header>`

**Props:** `title`, `subtitle` (optional), `$slot` (optional action buttons)

```blade
{{-- Usage --}}
<x-ui.page-header title="Clients" subtitle="Manage your client accounts">
    <x-ui.btn wire:click="openCreate" label="Add Client" icon="plus" />
</x-ui.page-header>
```

```blade
{{-- Component: resources/views/components/ui/page-header.blade.php --}}
@props(['title', 'subtitle' => null])
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-primary">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-sm text-secondary mt-0.5">{{ $subtitle }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex items-center gap-2">{{ $slot }}</div>
    @endif
</div>
```

---

### `<x-ui.stat-card>`

**Props:** `label`, `value`, `icon` (tabler icon name), `variant` (critical|warning|healthy|neutral)

```blade
{{-- Usage --}}
<x-ui.stat-card label="Expiring Soon" value="{{ $stats['critical'] }}" icon="alert-triangle" variant="critical" />
```

```blade
{{-- Component --}}
@props(['label', 'value', 'icon', 'variant' => 'neutral'])

@php
$variants = [
    'critical' => 'bg-red-50 border-red-200 text-red-600',
    'warning'  => 'bg-amber-50 border-amber-200 text-amber-600',
    'healthy'  => 'bg-green-50 border-green-200 text-green-600',
    'neutral'  => 'bg-white border-slate-200 text-slate-600',
];
$iconClass = $variants[$variant] ?? $variants['neutral'];
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
    <div class="p-3 rounded-lg border {{ $iconClass }}">
        <x-tabler-{{ $icon }} class="w-5 h-5" />
    </div>
    <div>
        <p class="text-2xl font-bold text-primary font-mono">{{ $value }}</p>
        <p class="text-xs text-secondary mt-0.5">{{ $label }}</p>
    </div>
</div>
```

---

### `<x-ui.badge-status>`

**Props:** `status` — one of: Active, Expiring, Expired, Cancelled

```blade
@props(['status'])
@php
$map = [
    'Active'    => 'badge badge-success badge-soft',
    'Expiring'  => 'badge badge-warning badge-soft',
    'Expired'   => 'badge badge-error badge-soft',
    'Cancelled' => 'badge badge-neutral badge-soft',
];
@endphp
<span class="{{ $map[$status] ?? 'badge badge-ghost' }}">{{ $status }}</span>
```

---

### `<x-ui.badge-payment>`

**Props:** `status` — one of: Pending, Invoiced, Paid, Renewed, Lapsed

```blade
@props(['status'])
@php
$map = [
    'Pending'  => 'badge badge-ghost',
    'Invoiced' => 'badge badge-info badge-soft',
    'Paid'     => 'badge badge-success badge-soft',
    'Renewed'  => 'badge badge-primary badge-soft',
    'Lapsed'   => 'badge badge-error badge-soft',
];
@endphp
<span class="{{ $map[$status] ?? 'badge badge-ghost' }}">{{ $status }}</span>
```

---

### `<x-ui.data-table>`

**Props:** `$headers` (array of column names), `$slot` (tbody rows)

```blade
{{-- Usage --}}
<x-ui.data-table :headers="['Client', 'Project', 'Service', 'Expiry', 'Status', '']">
    @foreach($subscriptions as $sub)
    <tr>
        <td>{{ $sub->project->client->name }}</td>
        {{-- ... --}}
    </tr>
    @endforeach
</x-ui.data-table>
```

```blade
{{-- Component --}}
@props(['headers' => []])
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    @foreach($headers as $header)
                        <th class="text-xs font-semibold text-secondary uppercase tracking-wide px-4 py-3">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
```

---

### `<x-ui.empty-state>`

**Props:** `icon`, `title`, `message`, `$slot` (optional action button)

```blade
@props(['icon' => 'inbox', 'title', 'message' => ''])
<div class="flex flex-col items-center justify-center py-20 text-center">
    <div class="p-4 rounded-full bg-slate-100 mb-4">
        <x-tabler-{{ $icon }} class="w-8 h-8 text-slate-400" />
    </div>
    <h3 class="text-base font-semibold text-primary mb-1">{{ $title }}</h3>
    @if($message)
        <p class="text-sm text-secondary max-w-sm">{{ $message }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
```

---

### `<x-ui.confirm-modal>`

**Props:** `title`, `message`, `confirmAction` (Livewire method name), `wire:model`

```blade
@props(['title' => 'Are you sure?', 'message' => '', 'confirmAction' => 'delete'])
<div x-data x-show="$wire.confirmDelete" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
        <div class="flex items-start gap-4">
            <div class="p-2 rounded-full bg-red-50">
                <x-tabler-alert-triangle class="w-5 h-5 text-red-500" />
            </div>
            <div>
                <h3 class="font-semibold text-primary">{{ $title }}</h3>
                <p class="text-sm text-secondary mt-1">{{ $message }}</p>
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
            <button class="btn btn-ghost btn-sm"
                    wire:click="$set('confirmDelete', false)">Cancel</button>
            <button class="btn btn-error btn-sm"
                    wire:click="{{ $confirmAction }}">Delete</button>
        </div>
    </div>
</div>
```

---

### `<x-ui.form-input>`

**Props:** `label`, `model` (wire:model name), `type` (default: text), `placeholder`, `error`, `prefix`, `suffix`

```blade
@props(['label', 'model', 'type' => 'text', 'placeholder' => '', 'error' => null, 'prefix' => null, 'suffix' => null])
<div class="form-control w-full">
    <label class="label">
        <span class="label-text font-medium text-sm">{{ $label }}</span>
    </label>
    <div class="flex items-center gap-0">
        @if($prefix)
            <span class="px-3 py-2 bg-slate-100 border border-r-0 border-slate-300 rounded-l-lg text-sm text-secondary">
                {{ $prefix }}
            </span>
        @endif
        <input
            type="{{ $type }}"
            wire:model="{{ $model }}"
            placeholder="{{ $placeholder }}"
            class="input input-bordered w-full {{ $prefix ? 'rounded-l-none' : '' }} {{ $suffix ? 'rounded-r-none' : '' }} {{ $error ? 'input-error' : '' }}"
        />
        @if($suffix)
            <span class="px-3 py-2 bg-slate-100 border border-l-0 border-slate-300 rounded-r-lg text-sm text-secondary">
                {{ $suffix }}
            </span>
        @endif
    </div>
    @if($error)
        <label class="label">
            <span class="label-text-alt text-error">{{ $error }}</span>
        </label>
    @endif
</div>
```

---

### `<x-ui.form-select>`

**Props:** `label`, `model`, `options` (associative array value => label), `placeholder`, `error`

```blade
@props(['label', 'model', 'options' => [], 'placeholder' => 'Select...', 'error' => null])
<div class="form-control w-full">
    <label class="label">
        <span class="label-text font-medium text-sm">{{ $label }}</span>
    </label>
    <select wire:model="{{ $model }}" class="select select-bordered w-full {{ $error ? 'select-error' : '' }}">
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    @if($error)
        <label class="label">
            <span class="label-text-alt text-error">{{ $error }}</span>
        </label>
    @endif
</div>
```

---

### `<x-ui.action-menu>`

**Props:** `editAction`, `deleteAction` — both are Livewire method calls

```blade
@props(['editAction', 'deleteAction'])
<div class="dropdown dropdown-end">
    <button tabindex="0" class="btn btn-ghost btn-xs btn-square">
        <x-tabler-dots-vertical class="w-4 h-4" />
    </button>
    <ul tabindex="0" class="dropdown-content menu shadow-lg bg-white border border-slate-200 rounded-xl z-10 w-36 p-1">
        <li>
            <button wire:click="{{ $editAction }}" class="flex items-center gap-2 text-sm">
                <x-tabler-edit class="w-4 h-4" /> Edit
            </button>
        </li>
        <li>
            <button wire:click="{{ $deleteAction }}" class="flex items-center gap-2 text-sm text-error">
                <x-tabler-trash class="w-4 h-4" /> Delete
            </button>
        </li>
    </ul>
</div>
```

---

## 🔘 Button Conventions

Always use FlyonUI/DaisyUI button classes. Never write custom button HTML outside a component.

| Purpose | Class |
|---|---|
| Primary action | `btn btn-primary btn-sm` |
| Destructive | `btn btn-error btn-sm` |
| Secondary / cancel | `btn btn-ghost btn-sm` |
| Outline | `btn btn-outline btn-sm` |
| Icon only | `btn btn-ghost btn-square btn-sm` |

---

## 📋 Form Layout Rules

- All forms use a `grid grid-cols-1 gap-4` or `grid grid-cols-2 gap-4` layout
- Full-width fields (notes, description) always `col-span-2`
- Always use `<x-ui.form-input>`, `<x-ui.form-select>`, `<x-ui.form-textarea>` — never raw inputs
- Form submit button always bottom-right: `flex justify-end mt-6`
- Dollar amount inputs always use `prefix="$"`

---

## 🧭 Sidebar Nav Items

| Label | Route | Tabler Icon |
|---|---|---|
| Dashboard | `dashboard` | `layout-dashboard` |
| Clients | `clients.index` | `users` |
| Projects | `projects.index` | `folder` |
| Subscriptions | `subscriptions.index` | `refresh` |
| Renewals | `renewals.index` | `calendar-due` |
| Invoices | `invoices.index` | `file-invoice` |
| — divider — | | |
| Settings | `settings.index` | `settings` |

Active state: left border accent `border-l-2 border-blue-500` + `text-white bg-slate-800`

---

## 📊 Dashboard Layout

```
Row 1: 6 stat cards (grid-cols-3 on desktop, grid-cols-2 on tablet)
  ├── Critical (red)        ← subscriptions ≤7 days
  ├── Warning (amber)       ← subscriptions ≤30 days
  ├── Healthy (green)       ← subscriptions >30 days
  ├── Unpaid Invoices       ← renewals with status Invoiced
  ├── Overdue Invoices      ← invoices past due date
  └── Total Clients         ← neutral

Row 2: Two-column table section (grid-cols-2)
  ├── LEFT:  🔴 Critical Subscriptions (table, top 10)
  └── RIGHT: 🟡 Warning Subscriptions (table, top 10)
```

---

## 🚦 Traffic Light Rules (enforced in component)

| Condition | Color | Badge text | Icon |
|---|---|---|---|
| expiry ≤ 7 days | Red | `CRITICAL` | `alert-triangle` |
| expiry ≤ 30 days | Amber | `EXPIRING` | `clock` |
| expiry > 30 days | Green | `ACTIVE` | `circle-check` |
| expired | Red | `EXPIRED` | `x-circle` |
| cancelled | Grey | `CANCELLED` | `minus-circle` |

---

## 🚫 Things the Agent Must NEVER Do

1. Write inline styles — use Tailwind classes or CSS variables only
2. Duplicate HTML patterns — make a component instead
3. Write raw `<input>`, `<select>`, `<textarea>` outside of form components
4. Use any font other than DM Sans / DM Mono
5. Invent new color values — use only the token palette above
6. Mix Alpine.js and Livewire state for the same thing — pick one per feature
7. Put business logic in Blade views — it belongs in Livewire components or Services
8. Hardcode any business name, email, or payment details — always use `Setting::get()`

---

*End of DESIGN_SYSTEM.md*
