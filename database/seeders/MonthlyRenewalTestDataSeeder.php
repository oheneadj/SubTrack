<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Enums\SubscriptionRenewalType;
use App\Enums\SubscriptionStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds a small, clearly-labeled set of RecurringMonthly and OneTimeMonthly
 * subscriptions covering the scenarios that matter for testing monthly-cycle
 * behavior: normal/upcoming, each reminder window, and several degrees of
 * "overdue" (to exercise Subscription::missed_payments_count at 1, 2, and 3
 * missed cycles).
 */
class MonthlyRenewalTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $client = Client::firstOrCreate(
            ['email' => 'monthly-test@example.test'],
            ['name' => 'Monthly Test Client', 'company_name' => 'Monthly Test Co']
        );

        $project = Project::firstOrCreate(
            ['client_id' => $client->id, 'project_name' => 'Monthly Renewal Test Project'],
            ['description' => 'Seeded project for testing monthly-cycle subscriptions.']
        );

        $provider = Provider::firstOrCreate(
            ['name' => 'Monthly Test Provider'],
            ['website' => 'https://example.test', 'support_email' => 'support@example.test']
        );

        // [renewal type, label, days until expiry (negative = overdue), status]
        $scenarios = [
            [SubscriptionRenewalType::RecurringMonthly, 'recurring-monthly-active', 25, SubscriptionStatus::Active],
            [SubscriptionRenewalType::RecurringMonthly, 'recurring-monthly-expiring-7d', 7, SubscriptionStatus::Expiring],
            [SubscriptionRenewalType::RecurringMonthly, 'recurring-monthly-expiring-3d', 3, SubscriptionStatus::Expiring],
            [SubscriptionRenewalType::RecurringMonthly, 'recurring-monthly-overdue-1cycle', -10, SubscriptionStatus::Expired],
            [SubscriptionRenewalType::RecurringMonthly, 'recurring-monthly-overdue-2cycles', -40, SubscriptionStatus::Expired],
            [SubscriptionRenewalType::RecurringMonthly, 'recurring-monthly-overdue-3cycles', -70, SubscriptionStatus::Expired],

            [SubscriptionRenewalType::OneTimeMonthly, 'onetime-monthly-active', 25, SubscriptionStatus::Active],
            [SubscriptionRenewalType::OneTimeMonthly, 'onetime-monthly-expiring-7d', 7, SubscriptionStatus::Expiring],
            [SubscriptionRenewalType::OneTimeMonthly, 'onetime-monthly-overdue-1cycle', -10, SubscriptionStatus::Expired],
            [SubscriptionRenewalType::OneTimeMonthly, 'onetime-monthly-overdue-2cycles', -40, SubscriptionStatus::Expired],
        ];

        foreach ($scenarios as [$renewalType, $label, $daysUntilExpiry, $status]) {
            $expiryDate = $now->copy()->addDays($daysUntilExpiry);
            $purchaseDate = $expiryDate->copy()->subMonth();

            Subscription::updateOrCreate(
                ['project_id' => $project->id, 'domain_name' => "{$label}.test"],
                [
                    'provider_id' => $provider->id,
                    'service_type' => ServiceType::Hosting,
                    'renewal_type' => $renewalType,
                    'purchase_date' => $purchaseDate->toDateString(),
                    'expiry_date' => $expiryDate->toDateString(),
                    'purchase_cost_usd' => 1500,
                    'renewal_cost_usd' => 1500,
                    'status' => $status,
                ]
            );
        }

        $this->command?->info('Seeded '.count($scenarios).' monthly-cycle test subscriptions under "Monthly Test Client".');
    }
}
