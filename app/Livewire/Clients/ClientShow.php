<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class ClientShow extends Component
{
    public Client $client;

    public function mount(Client $client)
    {
        $this->client = $client->load(['projects.subscriptions', 'invoices' => function($query) {
            $query->latest()->limit(10);
        }]);
    }

    #[On('project-saved')]
    public function projectSaved(string $message): void
    {
        session()->flash('success', $message);
    }

    #[Computed]
    public function projects()
    {
        return $this->client->projects()->withCount('subscriptions')->get();
    }

    #[Computed]
    public function subscriptions()
    {
        return $this->client->subscriptions()->whereHas('project')->with('project')->get();
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
            'total_billed' => $this->client->invoices()->where('status', 'Paid')->sum('total_amount'),
            'pending_amount' => $this->client->invoices()->where('status', 'Sent')->sum('total_amount'),
            'active_subscriptions' => $this->client->subscriptions()->where('status', 'Active')->count(),
            'project_count' => $this->client->projects()->count(),
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.clients.client-show');
    }
}
