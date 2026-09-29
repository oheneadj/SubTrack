<?php

declare(strict_types=1);

namespace App\Livewire\MailTemplates;

use App\Livewire\Concerns\Notifies;
use App\Mail\InvoiceMail;
use App\Mail\SubscriptionReminderMail;
use App\Mail\UserInviteMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\MailTemplate;
use App\Models\Project;
use App\Models\Subscription;
use App\Services\EmailLogger;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MailTemplateIndex extends Component
{
    use Notifies;

    public bool $showEditModal = false;

    public ?MailTemplate $editingTemplate = null;

    public string $editSubject = '';

    public string $editBody = '';

    protected $rules = [
        'editSubject' => 'required|string|max:500',
        'editBody' => 'required|string',
    ];

    #[Computed]
    public function templates()
    {
        return MailTemplate::orderBy('name')->get();
    }

    public function edit(string $ulid): void
    {
        $this->editingTemplate = MailTemplate::where('ulid', $ulid)->firstOrFail();
        $this->editSubject = $this->editingTemplate->subject;
        $this->editBody = $this->editingTemplate->body;
        $this->showEditModal = true;
    }

    public function save()
    {
        $this->validate();

        $this->editingTemplate->update([
            'subject' => $this->editSubject,
            'body' => $this->editBody,
        ]);

        $this->showEditModal = false;
        $this->reset(['editingTemplate', 'editSubject', 'editBody']);

        $this->notifySuccess('Template updated successfully.');
    }

    public function sendTest(string $ulid): void
    {
        $template = MailTemplate::where('ulid', $ulid)->firstOrFail();
        $user = auth()->user();

        try {
            $mail = match ($template->slug) {
                'user-invite' => new UserInviteMail(
                    $user->name,
                    $user->email,
                    'p4ssw0rd!',
                    route('dashboard')
                ),
                'subscription-reminder' => new SubscriptionReminderMail(
                    Subscription::first() ?? new Subscription([
                        'provider' => 'Example Provider',
                        'expiry_date' => now()->addDays(7),
                    ])
                ),
                'invoice-mail' => new InvoiceMail(
                    Invoice::with(['client', 'project'])->first() ?? (function () {
                        $invoice = new Invoice([
                            'invoice_number' => 'INV-TEST-001',
                            'due_date' => now()->addDays(14),
                            'total_amount' => 125000, // 1250.00 in cents
                        ]);
                        // Mock relationships for the test email to avoid crash
                        $invoice->setRelation('client', new Client(['name' => 'Test Client']));
                        $invoice->setRelation('project', new Project(['project_name' => 'Test Project']));

                        return $invoice;
                    })()
                ),
                default => throw new \Exception('Unknown template type'),
            };

            app(EmailLogger::class)->track($mail, $user->email, $user->name, userId: $user->id);

            // sendNow(), not send()/queue() — these mailables implement ShouldQueue
            // (so real sends go through the emails queue), but a test send may
            // involve an unsaved mock model (e.g. no Invoice exists yet) that
            // can't survive queue job serialization. sendNow() always sends
            // in-process regardless of ShouldQueue, sidestepping that entirely.
            Mail::to($user->email)->sendNow($mail);

            $this->notifySuccess("Test email for '{$template->name}' sent to your email.");
        } catch (\Exception $e) {
            $this->notifyError('Failed to send test email: '.$e->getMessage());
        }
    }

    public function resetToDefault(string $ulid): void
    {
        $template = MailTemplate::where('ulid', $ulid)->firstOrFail();

        $defaults = [
            'user-invite' => [
                'subject' => "You've been invited to ".config('app.name'),
                'body' => "You've been invited to join {app_name}. In order to access your new account, please use the temporary login credentials provided below:",
            ],
            'subscription-reminder' => [
                'subject' => 'Service Renewal Reminder — {service_name}',
                'body' => 'This is an automated notification regarding the active service attached to your project: {project_name}.',
            ],
            'invoice-mail' => [
                'subject' => 'New Invoice: {invoice_number} from '.config('app.name'),
                'body' => 'Please find the summary of your latest invoice attached for your project: {project_name}.',
            ],
        ];

        if (isset($defaults[$template->slug])) {
            $this->editSubject = $defaults[$template->slug]['subject'];
            $this->editBody = $defaults[$template->slug]['body'];

            $this->dispatch('notify', type: 'success', message: "Fields reset to default values. Don't forget to save.");
        }
    }

    public function render()
    {
        return view('livewire.mail-templates.mail-template-index')
            ->layout('components.layouts.app');
    }
}
