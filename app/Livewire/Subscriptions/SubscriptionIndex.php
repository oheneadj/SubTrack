<?php

declare(strict_types=1);

namespace App\Livewire\Subscriptions;

use App\Livewire\Concerns\Notifies;
use App\Models\Subscription;
use App\Traits\WithSorting;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Subscriptions list — search, filters, bulk actions, and CSV export. */
class SubscriptionIndex extends Component
{
    use Notifies, WithPagination, WithSorting;

    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    public string $search = '';

    public ?string $filterService = null;

    public ?string $filterStatus = null;

    public ?string $filterRenewalType = null;

    public string $filterRenewalFrom = '';

    public string $filterRenewalTo = '';

    public ?int $selectedSubscriptionId = null;

    // Bulk Actions
    public array $selectedSubscriptions = [];

    public bool $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterService' => ['except' => null],
        'filterStatus' => ['except' => null],
        'filterRenewalType' => ['except' => null],
        'filterRenewalFrom' => ['except' => ''],
        'filterRenewalTo' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The filtered/searched subscriptions query, shared by the paginated
     * list and the CSV export so both always see identical results.
     *
     * @return Builder<Subscription>
     */
    private function filteredQuery(): Builder
    {
        return Subscription::query()
            ->with(['client', 'project.client', 'provider'])
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('domain_name', 'like', "%{$this->search}%")
                ->orWhereHas('provider', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('project', fn ($p) => $p->where('project_name', 'like', "%{$this->search}%"))
                ->orWhereHas('project.client', fn ($c) => $c->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterService, fn ($q) => $q->where('service_type', $this->filterService))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterRenewalType, fn ($q) => $q->where('renewal_type', $this->filterRenewalType))
            ->when($this->filterRenewalFrom, fn ($q) => $q->whereDate('expiry_date', '>=', $this->filterRenewalFrom))
            ->when($this->filterRenewalTo, fn ($q) => $q->whereDate('expiry_date', '<=', $this->filterRenewalTo));
    }

    #[Computed]
    public function subscriptions()
    {
        return $this->sortedQuery($this->filteredQuery())->paginate(15);
    }

    /**
     * Apply sorting, with a special case for "client_name" since the
     * effective client comes from either subscriptions.client_id or
     * projects.client_id and isn't a plain column WithSorting can order by.
     *
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    private function sortedQuery(Builder $query): Builder
    {
        if ($this->sortColumn !== 'client_name') {
            return $this->applySorting($query);
        }

        // sortDirection is a public Livewire property (client-settable), so never interpolate it
        // into raw SQL directly — constrain it to a known-safe value first.
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return $query
            ->select('subscriptions.*')
            ->leftJoin('clients', 'clients.id', '=', 'subscriptions.client_id')
            ->leftJoin('projects', 'projects.id', '=', 'subscriptions.project_id')
            ->leftJoin('clients as project_clients', 'project_clients.id', '=', 'projects.client_id')
            ->orderByRaw("COALESCE(clients.name, project_clients.name) {$direction}");
    }

    public function confirmDelete(string $ulid): void
    {
        $this->selectedSubscriptionId = Subscription::where('ulid', $ulid)->firstOrFail()->id;
        $this->dispatch('open-modal', id: 'confirm-delete-subscription');
    }

    public function delete(): void
    {
        if ($this->selectedSubscriptionId) {
            Subscription::findOrFail($this->selectedSubscriptionId)->delete();
            $this->selectedSubscriptionId = null;
            $this->notifySuccess('Subscription deleted successfully.');
        }
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedSubscriptions = $this->subscriptions->pluck('ulid')->toArray();
        } else {
            $this->selectedSubscriptions = [];
        }
    }

    public function applyBulkStatus(string $status): void
    {
        if (empty($this->selectedSubscriptions)) {
            return;
        }

        Subscription::whereIn('ulid', $this->selectedSubscriptions)->update(['status' => $status]);

        $this->selectedSubscriptions = [];
        $this->selectAll = false;

        $this->notifySuccess('Selected subscriptions updated successfully.');
    }

    /**
     * Stream a CSV of the currently filtered/searched subscriptions.
     * Columns: client name/email, subscription name, renewal type,
     * subscription (purchase) date, renewal (expiry) date, and status.
     */
    public function export(): StreamedResponse
    {
        $query = $this->filteredQuery();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=subscriptions-export-'.now()->format('Y-m-d').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Client Name', 'Client Email', 'Subscription Name', 'Renewal Type',
                'Subscription Date', 'Renewal Date', 'Status',
            ]);

            $query->chunk(100, function ($subscriptions) use ($file) {
                foreach ($subscriptions as $sub) {
                    fputcsv($file, [
                        $sub->effective_client?->name ?? 'N/A',
                        $sub->effective_client?->email ?? 'N/A',
                        $sub->domain_name ?: $sub->service_type->label(),
                        $sub->renewal_type->label(),
                        $sub->purchase_date->format('Y-m-d'),
                        $sub->expiry_date->format('Y-m-d'),
                        $sub->status->value,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.subscriptions.subscription-index');
    }
}
