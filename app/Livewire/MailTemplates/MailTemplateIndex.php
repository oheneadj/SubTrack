<?php

namespace App\Livewire\MailTemplates;

use App\Models\MailTemplate;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserInviteMail;
use App\Mail\SubscriptionReminderMail;
use App\Mail\InvoiceMail;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Project;

class MailTemplateIndex extends Component
{
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

    public function edit(int $id)
    {
        $this->editingTemplate = MailTemplate::findOrFail($id);
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
        
        session()->flash('success', 'Template updated successfully.');
    }

    public function sendTest(int $id)
    {
        $template = MailTemplate::findOrFail($id);
        $user = auth()->user();

        try {
            match($template->slug) {
                'user-invite' => Mail::to($user->email)->send(new UserInviteMail(
                    $user->name,
                    $user->email,
                    'p4ssw0rd!',
                    route('dashboard')
                )),
                'subscription-reminder' => Mail::to($user->email)->send(new SubscriptionReminderMail(
                    Subscription::first() ?? new Subscription([
                        'provider' => 'Example Provider',
                        'expiry_date' => now()->addDays(7),
                    ])
                )),
                'invoice-mail' => Mail::to($user->email)->send(new InvoiceMail(
                    Invoice::with(['client', 'project'])->first() ?? (function() {
                        $invoice = new Invoice([
                            'invoice_number' => 'INV-TEST-001',
                            'due_date' => now()->addDays(14),
                            'total_amount' => 1250.00,
                        ]);
                        // Mock relationships for the test email to avoid crash
                        $invoice->setRelation('client', new Client(['name' => 'Test Client']));
                        $invoice->setRelation('project', new Project(['project_name' => 'Test Project']));
                        return $invoice;
                    })()
                )),
                default => throw new \Exception('Unknown template type'),
            };

            session()->flash('success', "Test email for '{$template->name}' sent to your email.");
        } catch (\Exception $e) {
            session()->flash('error', "Failed to send test email: " . $e->getMessage());
        }
    }

    public function resetToDefault(int $id)
    {
        $template = MailTemplate::findOrFail($id);
        
        $defaults = [
            'user-invite' => [
                'subject' => "You've been invited to " . config('app.name'),
                'body' => "You've been invited to join {app_name}. Here are your login credentials:\n\nEmail: {user_email}\nPassword: {password}\n\nFor security, please change your password after your first login. If you did not expect this invitation, you can safely ignore this email.\n\nBest regards,\n{company_name}",
            ],
            'subscription-reminder' => [
                'subject' => 'Service Renewal Reminder — {service_name}',
                'body' => "This is an automated notification regarding your service for {project_name}.\n\nService: {service_name}\nProvider: {provider}\nExpiry Date: {expiry_date}\nTime Remaining: {days_remaining}\n\nTo ensure continued service and avoid any potential downtime, please arrange for renewal as soon as possible.\n\nThank you for choosing {company_name}.",
            ],
            'invoice-mail' => [
                'subject' => 'New Invoice: {invoice_number} from ' . config('app.name'),
                'body' => "Please find attached the invoice for your project: {project_name}.\n\nInvoice Number: {invoice_number}\nDue Date: {due_date}\nTotal Amount: {total_amount}\n\nYou can find the full breakdown in the attached PDF file.\n\nIf you have any questions regarding this invoice, please don't hesitate to reach out to us at {company_email}.\n\nBest regards,\n{company_name} Team",
            ],
        ];

        if (isset($defaults[$template->slug])) {
            $this->editSubject = $defaults[$template->slug]['subject'];
            $this->editBody = $defaults[$template->slug]['body'];
            
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Fields reset to default values. Don\'t forget to save.']);
        }
    }

    public function render()
    {
        return view('livewire.mail-templates.mail-template-index')
            ->layout('components.layouts.app');
    }
}
