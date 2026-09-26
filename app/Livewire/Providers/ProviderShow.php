<?php

declare(strict_types=1);

namespace App\Livewire\Providers;

use App\Enums\SubscriptionStatus;
use App\Models\Provider;
use App\Models\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ProviderShow extends Component
{
    use WithPagination;

    public Provider $provider;

    public function mount(Provider $provider)
    {
        $this->provider = $provider;
    }

    #[Computed]
    public function subscriptions()
    {
        return $this->provider->subscriptions()
            ->with(['client', 'project.client'])
            ->orderBy('expiry_date')
            ->paginate(15);
    }

    #[Computed]
    public function stats()
    {
        $subs = $this->provider->subscriptions()->get();

        return [
            'total_subscriptions' => $subs->count(),
            'active_subscriptions' => $subs->where('status', SubscriptionStatus::Active)->count(),
            'total_value' => $subs->sum('renewal_cost_usd') / 100,
        ];
    }

    public function deleteSubscription(string $ulid): void
    {
        Subscription::where('ulid', $ulid)->firstOrFail()->delete();
        unset($this->stats); // Recalculate stats
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.providers.provider-show');
    }
}
