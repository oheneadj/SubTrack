<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.auth')]
class ForcePasswordChange extends Component
{
    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        $user->update([
            'password' => $this->password, 
            'requires_password_change' => false,
        ]);

        session()->flash('success', 'Your password has been secured successfully. Welcome to your dashboard!');

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.force-password-change');
    }
}
