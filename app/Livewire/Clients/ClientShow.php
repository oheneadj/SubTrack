<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Livewire\Concerns\Notifies;
use App\Models\Client;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class ClientShow extends Component
{
    use Notifies;

    public Client $client;

    public function mount(Client $client)
    {
        $this->client = $client->load(['projects.subscriptions', 'invoices' => function ($query) {
            $query->latest()->limit(10);
        }]);
    }

    #[On('project-saved')]
    public function projectSaved(string $message): void
    {
        $this->notifySuccess($message);
    }

    /** Refresh the client after an edit made via the shared modal. */
    #[On('client-saved')]
    public function clientSaved(string $message): void
    {
        $this->client->refresh();
        $this->notifySuccess($message);
    }

    #[Computed]
    public function projects()
    {
        return $this->client->projects()->withCount('subscriptions')->get();
    }

    #[Computed]
    public function directSubscriptions()
    {
        return $this->client->directSubscriptions()->with(['provider'])->orderBy('expiry_date')->get();
    }

    #[Computed]
    public function subscriptions()
    {
        $direct = $this->client->directSubscriptions()->with('provider')->get();
        $viaProjects = $this->client->projectSubscriptions()->with('project', 'provider')->get();

        return $direct->merge($viaProjects)->sortBy('status');
    }

    #[Computed]
    public function invoices()
    {
        return $this->client->invoices()->latest()->paginate(10);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total_billed' => $this->client->invoices()->whereIn('status', ['Paid', 'Partially Paid'])->sum('amount_paid') / 100,
            'pending_amount' => $this->client->invoices()->whereIn('status', ['Sent', 'Overdue'])->sum('total_amount') / 100,
            'active_subscriptions' => $this->client->directSubscriptions()->where('status', 'Active')->count()
                + $this->client->projectSubscriptions()->where('status', 'Active')->count(),
            'project_count' => $this->client->projects()->count(),
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.clients.client-show');
    }
}
