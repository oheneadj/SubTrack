<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $templates = [
            [
                'slug'        => 'user-invite',
                'name'        => 'User Invitation',
                'subject'     => "You've been invited to {app_name}",
                'body'        => "You've been invited to join {app_name}. In order to access your new account, please use the temporary login credentials provided below:",
                'description' => 'Sent when a new user is invited to the platform with temporary credentials.',
                'variables'   => json_encode([
                    '{user_name}',
                    '{user_email}',
                    '{temporary_password}',
                    '{login_url}',
                    '{app_name}',
                    '{company_name}',
                ]),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'subscription-reminder',
                'name'        => 'Subscription Reminder',
                'subject'     => 'Service Renewal Reminder — {service_name}',
                'body'        => 'This is an automated notification regarding the active service attached to your project: {project_name}.',
                'description' => 'Sent when a subscription is approaching its expiry date.',
                'variables'   => json_encode([
                    '{client_name}',
                    '{project_name}',
                    '{service_name}',
                    '{provider_name}',
                    '{expiry_date}',
                    '{days_remaining}',
                    '{renewal_cost}',
                    '{company_name}',
                ]),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'invoice-mail',
                'name'        => 'Invoice Email',
                'subject'     => 'New Invoice: {invoice_number} from {company_name}',
                'body'        => 'Please find the summary of your latest invoice attached for your project: {project_name}.',
                'description' => 'Sent when an invoice is generated and emailed to a client.',
                'variables'   => json_encode([
                    '{client_name}',
                    '{project_name}',
                    '{invoice_number}',
                    '{due_date}',
                    '{total_amount}',
                    '{company_name}',
                    '{company_email}',
                    '{company_contact_details}',
                ]),
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('mail_templates')->insertOrIgnore($template);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mail_templates')->whereIn('slug', [
            'user-invite',
            'subscription-reminder',
            'invoice-mail',
        ])->delete();
    }
};
