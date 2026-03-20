<?php

namespace App\Livewire\Providers;

use App\Models\Provider;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class ProviderIndex extends Component
{
    use WithPagination;

    public string $search = '';
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
            'name' => 'required|string|max:255|unique:providers,name' . ($this->editingId ? ',' . $this->editingId : ''),
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

    public function edit(int $id): void
    {
        $provider = Provider::findOrFail($id);
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
            session()->flash('success', 'Provider updated successfully.');
        } else {
            Provider::create($data);
            session()->flash('success', 'Provider added successfully.');
        }

        $this->showModal = false;
    }

    public function openDeleteModal(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Provider::findOrFail($this->deletingId)->delete();
            session()->flash('success', 'Provider deleted.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $providers = Provider::withCount('subscriptions')
            ->where('name', 'like', "%{$this->search}%")
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.providers.provider-index', [
            'providers' => $providers
        ]);
    }
}
