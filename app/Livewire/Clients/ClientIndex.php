<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Models\Client;
use App\Traits\WithSorting;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ClientIndex extends Component
{
    use WithPagination, WithSorting;

    public string $sortColumn = 'name';

    public string $sortDirection = 'asc';

    // Table state
    public string $search = '';

    // Delete modal state
    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    public string $deletePassword = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    /** Flash the success message from the shared client-form modal after a create/update. */
    #[On('client-saved')]
    public function clientSaved(string $message): void
    {
        session()->flash('success', $message);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openDeleteModal(string $ulid): void
    {
        $this->deletingId = Client::where('ulid', $ulid)->firstOrFail()->id;
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

    public function render(): View
    {
        return view('livewire.clients.client-index', [
            'clients' => $this->applySorting(Client::search($this->search)
                ->withCount('projects'))
                ->paginate(15),
        ])->layout('layouts.app');
    }
}
