<?php

declare(strict_types=1);

namespace App\Livewire\MailTemplates;

use App\Mail\GenericClientMail;
use App\Models\Client;
use App\Models\MailTemplate;
use App\Models\Subscription;
use App\Services\ClientMailPersonalizer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class DirectMailer extends Component
{
    use WithPagination;

    public array $selectedClients = [];

    public ?string $selectedTemplate = null;

    public ?int $selectedSubscriptionId = null;

    public string $subject = '';

    public string $body = '';

    public string $search = '';

    public bool $selectAll = false;

    /** Whether the user has typed into subject/body since the last template was applied — guards against silently overwriting a manual draft. */
    public bool $hasManualEdits = false;

    public bool $showPreview = false;

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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function clients()
    {
        return Client::search($this->search)->orderBy('name')->simplePaginate(6);
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

    /** The first selected client, used as the stand-in recipient for the message preview. */
    #[Computed]
    public function previewClient(): ?Client
    {
        return $this->selectedClientModels->first();
    }

    /**
     * The subject/body with placeholders rendered for the preview client, so the user can
     * see exactly what a recipient will receive before sending to everyone.
     *
     * @return array{subject: string, body: string}
     */
    #[Computed]
    public function previewRendered(): array
    {
        if (! $this->previewClient) {
            return ['subject' => $this->subject, 'body' => $this->body];
        }

        $subscription = $this->selectedSubscriptionId ? Subscription::find($this->selectedSubscriptionId) : null;

        return app(ClientMailPersonalizer::class)->render(
            $this->previewClient,
            $this->subject,
            $this->body,
            $subscription,
        );
    }

    public function openPreview(): void
    {
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
    }

    public function updatedSelectedTemplate($slug)
    {
        if ($slug) {
            $template = MailTemplate::getBySlug($slug);
            if ($template) {
                $this->subject = $template->subject;
                $this->body = $template->body;
                $this->hasManualEdits = false;
            }
        }
    }

    public function updatedSubject(): void
    {
        $this->hasManualEdits = true;
    }

    public function updatedBody(): void
    {
        $this->hasManualEdits = true;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedClients = Client::search($this->search)->pluck('ulid')->toArray();
        } else {
            $this->selectedClients = [];
        }
    }

    public function send()
    {
        $this->validate();

        $rateLimitKey = 'direct-mailer-send:'.auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            session()->flash('error', "Too many send attempts. Please wait {$seconds} seconds before trying again.");

            return;
        }

        RateLimiter::hit($rateLimitKey, decaySeconds: 60);

        $clients = Client::whereIn('ulid', $this->selectedClients)->get();
        $subscription = $this->selectedSubscriptionId ? Subscription::find($this->selectedSubscriptionId) : null;

        foreach ($clients as $client) {
            Mail::to($client->email)->queue(new GenericClientMail(
                $client,
                $this->subject,
                $this->body,
                $subscription
            ));
        }

        $count = $clients->count();

        $this->reset(['selectedClients', 'selectedTemplate', 'subject', 'body', 'selectAll', 'hasManualEdits']);
        session()->flash('success', "Queued emails to {$count} clients for delivery.");
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.mail-templates.direct-mailer');
    }
}
