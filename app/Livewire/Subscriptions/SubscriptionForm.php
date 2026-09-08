<?php

declare(strict_types=1);

namespace App\Livewire\Subscriptions;

use App\Enums\SubscriptionRenewalType;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Create/edit form for a subscription — also used for the "Add Subscription" flow. */
class SubscriptionForm extends Component
{
    public ?Subscription $subscription = null;

    public bool $isEditing = false;

    public ?int $client_id = null;

    public ?int $project_id = null;

    public ?int $provider_id = null;

    public string $service_type = 'Domain';

    public string $renewal_type = 'RecurringAnnually';

    public string $domain_name = '';

    public string $purchase_date = '';

    public string $expiry_date = '';

    public string $notes = '';

    public float $purchase_cost_usd = 0;

    public float $renewal_cost_usd = 0;

    public ?float $markup_percentage = null;

    public string $status = 'Active';

    public function mount(?Subscription $subscription = null): void
    {
        if ($subscription && $subscription->exists) {
            $this->subscription = $subscription;
            $this->isEditing = true;

            $this->client_id = $subscription->client_id ?? $subscription->project?->client_id;
            $this->project_id = $subscription->project_id;
            $this->provider_id = $subscription->provider_id;
            $this->service_type = $subscription->service_type->value;
            $this->renewal_type = $subscription->renewal_type->value;
            $this->domain_name = $subscription->domain_name ?? '';
            $this->purchase_date = $subscription->purchase_date?->format('Y-m-d') ?? '';
            $this->expiry_date = $subscription->expiry_date?->format('Y-m-d') ?? '';
            $this->purchase_cost_usd = round($subscription->purchase_cost_usd / 100, 2);
            $this->renewal_cost_usd = round($subscription->renewal_cost_usd / 100, 2);
            $this->markup_percentage = $subscription->markup_percentage !== null
                ? (float) $subscription->markup_percentage
                : null;
            $this->status = $subscription->status->value;
            $this->notes = $subscription->notes ?? '';
        } else {
            if (request()->has('projectId')) {
                $project = Project::where('ulid', request()->query('projectId'))->first();
                if ($project) {
                    $this->project_id = $project->id;
                    $this->client_id = $project->client_id;
                }
            } elseif (request()->has('clientId')) {
                $client = Client::where('ulid', request()->query('clientId'))->first();
                if ($client) {
                    $this->client_id = $client->id;
                }
            }
            $this->purchase_date = now()->format('Y-m-d');
            $this->recalculateExpiryDate();
        }
    }

    public function updatedClientId(): void
    {
        $this->project_id = null; // Reset project when client changes
    }

    /** Recompute the auto-generated expiry date whenever the renewal type changes. */
    public function updatedRenewalType(): void
    {
        $this->recalculateExpiryDate();
    }

    /** Recompute the auto-generated expiry date whenever the purchase date changes. */
    public function updatedPurchaseDate(): void
    {
        $this->recalculateExpiryDate();
    }

    /**
     * Auto-generate the expiry date from the purchase date for renewal types
     * that carry an implied duration. Leaves the field alone for bare
     * "One-time", which has no implied duration and is entered manually.
     */
    private function recalculateExpiryDate(): void
    {
        if (! $this->purchase_date) {
            return;
        }

        $computed = SubscriptionRenewalType::from($this->renewal_type)
            ->expiryFrom(CarbonImmutable::parse($this->purchase_date));

        if ($computed) {
            $this->expiry_date = $computed->format('Y-m-d');
        }
    }

    /** Whether the expiry date is auto-computed (and so read-only in the form). */
    public function getExpiryDateIsAutoComputedProperty(): bool
    {
        return SubscriptionRenewalType::from($this->renewal_type)->cycleMonths() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'provider_id' => 'required|exists:providers,id',
            'service_type' => 'required',
            'renewal_type' => 'required|in:'.implode(',', array_column(SubscriptionRenewalType::cases(), 'value')),
            'domain_name' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
            'expiry_date' => 'required|date|after:purchase_date',
            'purchase_cost_usd' => 'required|numeric|min:0',
            'renewal_cost_usd' => 'required|numeric|min:0',
            'markup_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'required',
            'notes' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        // Convert dollar UI values to integer cents before persisting
        $data['purchase_cost_usd'] = (int) round((float) $data['purchase_cost_usd'] * 100);
        $data['renewal_cost_usd'] = (int) round((float) $data['renewal_cost_usd'] * 100);

        // When a project is selected, client_id is always the project's client
        if ($data['project_id']) {
            $project = Project::find($data['project_id']);
            $data['client_id'] = $project?->client_id;
        } else {
            // No project picked — fall back to the client's catch-all "Unrelated" project.
            $data['project_id'] = Project::unrelatedFor(Client::findOrFail($data['client_id']))->id;
        }

        if ($this->isEditing) {
            $this->subscription->update($data);
            session()->flash('success', 'Subscription updated successfully.');
            $this->redirect(route('subscriptions.show', $this->subscription), navigate: true);
        } else {
            $subscription = Subscription::create($data);
            session()->flash('success', 'Subscription created successfully.');
            $this->redirect(route('subscriptions.show', $subscription), navigate: true);
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $projectsQuery = Project::orderBy('project_name');
        if ($this->client_id) {
            $projectsQuery->where('client_id', $this->client_id);
        }

        return view('livewire.subscriptions.subscription-form', [
            'clients' => Client::orderBy('name')->get(),
            'projects' => $projectsQuery->get(),
            'providers' => Provider::orderBy('name')->get(),
        ]);
    }
}
