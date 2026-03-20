<?php

namespace App\Livewire\Dev;

use Livewire\Component;
use App\Enums\SubscriptionStatus;
use App\Enums\PaymentStatus;

class ComponentsPreview extends Component
{
    public $inputText = '';
    public $selectValue = '';
    public $textareaText = '';
    public $confirmDelete = false;

    public function delete()
    {
        session()->flash('success', 'Delete action triggered successfully!');
        $this->confirmDelete = false;
    }

    public function render()
    {
        return view('livewire.dev.components-preview')
            ->layout('layouts.app');
    }
}
