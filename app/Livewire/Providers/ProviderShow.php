<?php

namespace App\Livewire\Providers;

use App\Models\Provider;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

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
            ->whereHas('project.client')
            ->with(['project.client'])
            ->orderBy('expiry_date')
            ->paginate(15);
    }

    #[Computed]
    public function stats()
    {
        $subs = $this->provider->subscriptions()->get();
        return [
            'total_subscriptions' => $subs->count(),
            'active_subscriptions' => $subs->where('status', \App\Enums\SubscriptionStatus::Active)->count(),
            'total_value' => $subs->sum('renewal_cost_usd'),
        ];
    }

    public function deleteSubscription(int $id)
    {
        \App\Models\Subscription::findOrFail($id)->delete();
        unset($this->stats); // Recalculate stats
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.providers.provider-show');
    }
}
