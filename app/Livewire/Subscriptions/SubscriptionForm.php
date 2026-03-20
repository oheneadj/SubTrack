<?php

namespace App\Livewire\Subscriptions;

use App\Enums\ServiceType;
use App\Enums\SubscriptionStatus;
use App\Models\Project;
use App\Models\Client;
use App\Models\Subscription;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SubscriptionForm extends Component
{
    public ?Subscription $subscription = null;
    public bool $isEditing = false;

    public ?int $client_id = null;
    public ?int $project_id = null;
    public ?int $provider_id = null;
    public string $service_type = 'Domain';
    public string $domain_name = '';
    public string $purchase_date = '';
    public string $expiry_date = '';
    public float $purchase_cost_usd = 0;
    public float $renewal_cost_usd = 0;
    public string $status = 'Active';

    public function mount(?Subscription $subscription = null): void
    {
        if ($subscription && $subscription->exists) {
            $this->subscription = $subscription;
            $this->isEditing = true;
            
            $this->client_id = $subscription->project->client_id;
            $this->project_id = $subscription->project_id;
            $this->provider_id = $subscription->provider_id;
            $this->service_type = $subscription->service_type->value;
            $this->domain_name = $subscription->domain_name ?? '';
            $this->purchase_date = $subscription->purchase_date?->format('Y-m-d') ?? '';
            $this->expiry_date = $subscription->expiry_date?->format('Y-m-d') ?? '';
            $this->purchase_cost_usd = (float) $subscription->purchase_cost_usd;
            $this->renewal_cost_usd = (float) $subscription->renewal_cost_usd;
            $this->status = $subscription->status->value;
        } else {
            // Check if projectId is passed in query string (from Project view "Add Subscription" button)
            if (request()->has('projectId')) {
                $project = Project::find(request()->query('projectId'));
                if ($project) {
                    $this->project_id = $project->id;
                    $this->client_id = $project->client_id;
                }
            }
            $this->purchase_date = now()->format('Y-m-d');
        }
    }

    public function updatedClientId()
    {
        $this->project_id = null; // Reset project when client changes
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'provider_id' => 'required|exists:providers,id',
            'service_type' => 'required',
            'domain_name' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
            'expiry_date' => 'required|date|after:purchase_date',
            'purchase_cost_usd' => 'required|numeric|min:0',
            'renewal_cost_usd' => 'required|numeric|min:0',
            'status' => 'required',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->isEditing) {
            $this->subscription->update($data);
            session()->flash('success', 'Subscription updated successfully.');
        } else {
            Subscription::create($data);
            session()->flash('success', 'Subscription created successfully.');
        }

        $this->redirect(route('subscriptions.index'), navigate: true);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $projectsQuery = \App\Models\Project::orderBy('project_name');
        if ($this->client_id) {
            $projectsQuery->where('client_id', $this->client_id);
        }

        return view('livewire.subscriptions.subscription-form', [
            'clients' => \App\Models\Client::orderBy('name')->get(),
            'projects' => $projectsQuery->get(),
            'providers' => \App\Models\Provider::orderBy('name')->get(),
        ]);
    }
}
