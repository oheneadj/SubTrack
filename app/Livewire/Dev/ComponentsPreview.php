<?php

declare(strict_types=1);

namespace App\Livewire\Dev;

use App\Livewire\Concerns\Notifies;
use Livewire\Component;

class ComponentsPreview extends Component
{
    use Notifies;

    public $inputText = '';

    public $selectValue = '';

    public $textareaText = '';

    public $confirmDelete = false;

    public function delete()
    {
        $this->notifySuccess('Delete action triggered successfully!');
        $this->confirmDelete = false;
    }

    public function render()
    {
        return view('livewire.dev.components-preview')
            ->layout('layouts.app');
    }
}
