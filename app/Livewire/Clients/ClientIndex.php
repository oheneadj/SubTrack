<?php

namespace App\Livewire\Clients;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientIndex extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';

    // Delete modal state
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletePassword = '';

    // Modal/Form state
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $company_name = '';

    protected function rules()
    {
        return [
            'name'         => 'required|string|max:255',
            'email'        => [
                'required', 
                'email', 
                'max:255', 
                Rule::unique('clients', 'email')->ignore($this->editingId)
            ],
            'phone'        => 'nullable|string|max:30',
            'company_name' => 'nullable|string|max:255',
        ];
    }

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        if (request()->has('edit')) {
            $this->edit((int) request()->query('edit'));
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openDeleteModal(int $id): void
    {
        $this->deletingId = $id;
        $this->deletePassword = '';
        $this->resetErrorBag('deletePassword');
        $this->showDeleteModal = true;
    }

    public function deleteWithPassword(): void
    {
        $this->validate(['deletePassword' => 'required|string']);

        if (! Hash::check($this->deletePassword, auth()->user()->password)) {
            $this->addError('deletePassword', 'Incorrect password.');
            return;
        }

        if ($this->deletingId) {
            $name = Client::findOrFail($this->deletingId)->name;
            Client::findOrFail($this->deletingId)->delete();
            session()->flash('success', "{$name} has been deleted.");
        }

        $this->showDeleteModal = false;
        $this->reset('deletePassword', 'deletingId');
    }

    // Modal Actions
    public function openCreate(): void
    {
        $this->resetFields();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->resetFields();
        $client = Client::findOrFail($id);
        $this->editingId = $id;
        $this->name = $client->name;
        $this->email = $client->email;
        $this->phone = $client->phone ?? '';
        $this->company_name = $client->company_name ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            Client::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Client updated successfully.');
        } else {
            Client::create($data);
            session()->flash('success', 'Client created successfully.');
        }

        $this->showModal = false;
        $this->resetFields();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetFields();
    }

    private function resetFields(): void
    {
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->company_name = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.clients.client-index', [
            'clients' => Client::search($this->search)
                ->withCount('projects')
                ->latest()
                ->paginate(15),
        ])->layout('layouts.app');
    }
}
