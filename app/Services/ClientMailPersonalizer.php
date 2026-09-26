<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Setting;
use App\Models\Subscription;

/**
 * Renders a subject/body template's placeholder variables for a specific client.
 *
 * Shared by GenericClientMail (actual delivery) and DirectMailer's preview so
 * both use the exact same variable set instead of drifting apart.
 */
class ClientMailPersonalizer
{
    /**
     * @return array{subject: string, body: string}
     */
    public function render(Client $client, string $subject, string $body, ?Subscription $subscription = null): array
    {
        $vars = [
            '{client_name}' => $client->name,
            '{company_name}' => Setting::get('business_name', config('app.name')),
            '{company_email}' => Setting::get('business_email', ''),
            '{company_contact_details}' => Setting::get('business_phone', '').' '.Setting::get('business_website', ''),
            '{app_name}' => config('app.name'),
        ];

        if ($subscription) {
            $vars['{project_name}'] = $subscription->project?->project_name ?? '';
            $vars['{service_name}'] = $subscription->domain_name ?: ($subscription->service_type->label() ?? '');
            $vars['{provider}'] = $subscription->provider?->name ?? '';
            $vars['{expiry_date}'] = $subscription->expiry_date?->format('F j, Y') ?? '';
            $vars['{days_remaining}'] = $subscription->days_until_expiry ?? '';
        }

        return [
            'subject' => strtr($subject, $vars),
            'body' => strtr($body, $vars),
        ];
    }
}
