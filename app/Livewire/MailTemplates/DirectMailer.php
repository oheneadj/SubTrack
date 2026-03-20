<?php

namespace App\Livewire\MailTemplates;

use App\Models\Client;
use App\Models\MailTemplate;
use App\Mail\GenericClientMail;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Mail;

class DirectMailer extends Component
{
    public array $selectedClients = [];
    public ?string $selectedTemplate = null;
    public ?int $selectedSubscriptionId = null;
    public string $subject = '';
    public string $body = '';
    public string $search = '';
    public bool $selectAll = false;

    public function mount()
    {
        $clientId = request()->query('clientId');
        if ($clientId) {
            $this->selectedClients = [(string)$clientId];
        }

        $subscriptionId = request()->query('subscriptionId');
        if ($subscriptionId) {
            $this->selectedSubscriptionId = (int)$subscriptionId;
        }

        $templateSlug = request()->query('template');
        if ($templateSlug) {
            $this->selectedTemplate = $templateSlug;
            $this->updatedSelectedTemplate($templateSlug);
        }
    }

    protected $rules = [
        'selectedClients' => 'required|array|min:1',
        'subject' => 'required|string|max:255',
        'body' => 'required|string',
    ];

    #[Computed]
    public function clients()
    {
        return Client::search($this->search)->orderBy('name')->paginate(6);
    }

    #[Computed]
    public function templates()
    {
        return MailTemplate::orderBy('name')->get();
    }

    public function updatedSelectedTemplate($slug)
    {
        if ($slug) {
            $template = MailTemplate::getBySlug($slug);
            if ($template) {
                $this->subject = $template->subject;
                $this->body = $template->body;
            }
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedClients = $this->clients->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedClients = [];
        }
    }

    public function send()
    {
        $this->validate();

        $count = 0;
        $clients = Client::whereIn('id', $this->selectedClients)->get();
        $subscription = $this->selectedSubscriptionId ? \App\Models\Subscription::find($this->selectedSubscriptionId) : null;

        foreach ($clients as $client) {
            try {
                Mail::to($client->email)->send(new GenericClientMail(
                    $client,
                    $this->subject,
                    $this->body,
                    $subscription
                ));
                $count++;
            } catch (\Exception $e) {
                // Log or handle error for specific client
            }
        }

        $this->reset(['selectedClients', 'selectedTemplate', 'subject', 'body', 'selectAll']);
        session()->flash('success', "Successfully sent emails to {$count} clients.");
    }

    #[\Livewire\Attributes\Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.mail-templates.direct-mailer');
    }
}
