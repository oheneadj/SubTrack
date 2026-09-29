<?php

declare(strict_types=1);

namespace App\Livewire\Providers;

use App\Livewire\Concerns\Notifies;
use App\Models\Provider;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ProviderIndex extends Component
{
    use Notifies, WithPagination;

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
        $providers = Provider::withCount('subscriptions')
            ->where('name', 'like', "%{$this->search}%")
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.providers.provider-index', [
            'providers' => $providers,
        ]);
    }
}
