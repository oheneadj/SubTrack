<?php

declare(strict_types=1);

namespace App\Livewire\MailTemplates;

use App\Mail\GenericClientMail;
use App\Models\Client;
use App\Models\MailTemplate;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

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
        $clientUlid = request()->query('clientId');
        if ($clientUlid) {
            $this->selectedClients = [$clientUlid];
        }

        $subscriptionUlid = request()->query('subscriptionId');
        if ($subscriptionUlid) {
            $sub = Subscription::where('ulid', $subscriptionUlid)->first();
            $this->selectedSubscriptionId = $sub?->id;
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

    /** The clients currently selected as recipients, keyed by ulid — used to render the recipient avatar stack. */
    #[Computed]
    public function selectedClientModels()
    {
        return Client::whereIn('ulid', $this->selectedClients)->get()->keyBy('ulid');
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
            $this->selectedClients = $this->clients->pluck('ulid')->toArray();
        } else {
            $this->selectedClients = [];
        }
    }

    public function send()
    {
        $this->validate();

        $count = 0;
        $clients = Client::whereIn('ulid', $this->selectedClients)->get();
        $subscription = $this->selectedSubscriptionId ? Subscription::find($this->selectedSubscriptionId) : null;

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

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.mail-templates.direct-mailer');
    }
}
