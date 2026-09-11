<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Models\Client;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Create/edit form for a client, usable standalone or (via the
 * open-client-modal event) as the shared modal on both the clients index
 * and a single client's show page — mirrors ProjectForm's pattern so
 * "Edit Client" never needs to navigate away from wherever it was clicked.
 */
class ClientForm extends Component
{
    public ?Client $client = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $company_name = '';

    public bool $isModal = false;

    public function mount(?Client $client = null): void
    {
        if ($client && $client->exists) {
            $this->loadClient($client);
        }
    }

    /** Open the modal, loading an existing client by ulid or resetting for a new one. */
    #[On('open-client-modal')]
    public function openClientModal(?string $id = null): void
    {
        $this->resetValidation();

        if ($id) {
            $this->loadClient(Client::where('ulid', $id)->firstOrFail());
        } else {
            $this->client = null;
            $this->name = '';
            $this->email = '';
            $this->phone = '';
            $this->company_name = '';
        }

        $this->isModal = true;
    }

    /** Populate the form fields from an existing client. */
    private function loadClient(Client $client): void
    {
        $this->client = $client;
        $this->name = $client->name;
        $this->email = $client->email;
        $this->phone = $client->phone ?? '';
        $this->company_name = $client->company_name ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($this->client?->id),
            ],
            'phone' => 'nullable|string|max:30',
            'company_name' => 'nullable|string|max:255',
        ];
    }

    /** Create or update the client, closing the modal if opened as one. */
    public function save()
    {
        $data = $this->validate();

        if ($this->client && $this->client->exists) {
            $this->client->update($data);
            $message = 'Client updated successfully.';
        } else {
            Client::create($data);
            $message = 'Client created successfully.';
        }

        if ($this->isModal) {
            $this->js("window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'client-modal' } }))");
            $this->dispatch('client-saved', $message);
        } else {
            session()->flash('success', $message);

            return redirect()->route('clients.index');
        }
    }

    public function render()
    {
        $title = $this->client && $this->client->exists ? 'Edit Client' : 'Add New Client';

        return view('livewire.clients.client-form', ['pageTitle' => $title]);
    }
}
