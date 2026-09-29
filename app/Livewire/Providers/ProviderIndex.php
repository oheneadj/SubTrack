<?php

declare(strict_types=1);

namespace App\Livewire\Providers;

use App\Enums\PaymentStatus;
use App\Livewire\Concerns\Notifies;
use App\Models\Provider;
use App\Models\Renewal;
use App\Traits\WithSorting;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ProviderIndex extends Component
{
    use Notifies, WithPagination, WithSorting;

    public string $search = '';

    public string $sortColumn = 'name';

    public string $sortDirection = 'asc';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $website = '';

    public string $support_email = '';

    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:providers,name'.($this->editingId ? ','.$this->editingId : ''),
            'website' => 'nullable|url|max:255',
            'support_email' => 'nullable|email|max:255',
        ];
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'website', 'support_email']);
        $this->showModal = true;
    }

    public function edit(string $ulid): void
    {
        $provider = Provider::where('ulid', $ulid)->firstOrFail();
        $this->editingId = $provider->id;
        $this->name = $provider->name;
        $this->website = $provider->website ?? '';
        $this->support_email = $provider->support_email ?? '';

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            Provider::findOrFail($this->editingId)->update($data);
            $this->notifySuccess('Provider updated successfully.');
        } else {
            Provider::create($data);
            $this->notifySuccess('Provider added successfully.');
        }

        $this->showModal = false;
    }

    public function openDeleteModal(string $ulid): void
    {
        $this->deletingId = Provider::where('ulid', $ulid)->firstOrFail()->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Provider::findOrFail($this->deletingId)->delete();
            $this->notifySuccess('Provider deleted.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        // Total actually paid to this provider (Paid/Renewed renewals only,
        // same definition as RevenueService::totalProviderCosts()) —
        // a correlated subquery selected as a plain column so it can be
        // sorted like any other, and the dashboard's "Provider Costs
        // Breakdown" card (capped at the top 5) can link here for the
        // full, sortable list.
        $costSubquery = Renewal::query()
            ->selectRaw('coalesce(sum(renewals.provider_cost_usd), 0)')
            ->join('subscriptions', 'subscriptions.id', '=', 'renewals.subscription_id')
            ->whereColumn('subscriptions.provider_id', 'providers.id')
            ->whereIn('renewals.payment_status', [PaymentStatus::Paid, PaymentStatus::Renewed]);

        $query = Provider::withCount('subscriptions')
            ->addSelect(['total_cost_cents' => $costSubquery])
            ->where('name', 'like', "%{$this->search}%");

        $providers = $this->applySorting($query)->paginate(15);

        return view('livewire.providers.provider-index', [
            'providers' => $providers,
        ]);
    }
}
