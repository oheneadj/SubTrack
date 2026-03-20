<?php

namespace Database\Seeders;

use App\Models\MailTemplate;
use Illuminate\Database\Seeder;

class MailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug'        => 'user-invite',
                'name'        => 'User Invitation',
                'subject'     => "You've been invited to " . config('app.name'),
                'body'        => "You've been invited to join {app_name}. Here are your login credentials:\n\nEmail: {user_email}\nPassword: {password}\n\nFor security, please change your password after your first login. If you did not expect this invitation, you can safely ignore this email.",
                'description' => 'Sent when a new user is invited to the system. Contains login credentials.',
                'variables'   => ['{user_name}', '{user_email}', '{password}', '{login_url}', '{app_name}', '{company_name}', '{company_email}', '{company_contact_details}'],
            ],
            [
                'slug'        => 'subscription-reminder',
                'name'        => 'Subscription Reminder',
                'subject'     => 'Service Renewal Reminder — {service_name}',
                'body'        => "This is an automated notification regarding your service for {project_name}.\n\nService: {service_name}\nProvider: {provider}\nExpiry Date: {expiry_date}\nTime Remaining: {days_remaining}\n\nTo ensure continued service and avoid any potential downtime, please arrange for renewal as soon as possible.\n\nThank you for choosing us.",
                'description' => 'Sent to clients when a subscription is about to expire or has expired.',
                'variables'   => ['{client_name}', '{project_name}', '{service_name}', '{provider}', '{expiry_date}', '{days_remaining}', '{app_name}', '{company_name}', '{company_email}', '{company_contact_details}'],
            ],
            [
                'slug'        => 'invoice-mail',
                'name'        => 'Invoice Notification',
                'subject'     => 'New Invoice: {invoice_number} from ' . config('app.name'),
                'body'        => "Please find attached the invoice for your project: {project_name}.\n\nInvoice Number: {invoice_number}\nDue Date: {due_date}\nTotal Amount: {total_amount}\n\nYou can find the full breakdown in the attached PDF file.\n\nIf you have any questions regarding this invoice, please don't hesitate to reach out.\n\nBest regards,\n{app_name} Team",
                'description' => 'Sent to clients with their invoice attached as a PDF.',
                'variables'   => ['{client_name}', '{project_name}', '{invoice_number}', '{due_date}', '{total_amount}', '{app_name}', '{company_name}', '{company_email}', '{company_contact_details}'],
            ],
        ];

        foreach ($templates as $template) {
            MailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
