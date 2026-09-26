<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Client portal login page — the client enters their email to receive a magic link.
 */
#[Layout('components.layouts.public')]
class ClientLoginPage extends Component
{
    public string $email = '';

    public function render(): View
    {
        return view('livewire.client.client-login-page');
    }
}
