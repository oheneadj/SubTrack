<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckSubscriptionExpiries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subtrack:check-expiries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all subscriptions for upcoming expiries and send notifications';

    public function __construct(
        private readonly NotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Checking subscription expiries...');

        $configuredReminderDays = collect(explode(',', Setting::get('reminder_days', '30,14,7')))
            ->map(fn (string $day) => (int) trim($day))
            ->filter(fn (int $day) => $day > 0)
            ->values()
            ->all();

        $subscriptions = Subscription::where('status', '!=', SubscriptionStatus::Cancelled)->get();
        $processedCount = 0;
        $notifiedCount = 0;

        foreach ($subscriptions as $subscription) {
            $processedCount++;
            $daysLeft = $subscription->days_until_expiry;

            // 1. Update status if expired
            if ($subscription->expiry_date->isPast()) {
                if ($subscription->status !== SubscriptionStatus::Expired) {
                    $subscription->update(['status' => SubscriptionStatus::Expired]);
                    $this->warn("Subscription #{$subscription->id} ({$subscription->domain_name}) has EXPIRED.");
                    $this->notificationService->sendExpiryReminder($subscription);
                    $notifiedCount++;
                }

                continue;
            }

            // 2. Update status if expiring soon (<= 30 days)
            if ($daysLeft <= 30 && $daysLeft > 0 && $subscription->status === SubscriptionStatus::Active) {
                $subscription->update(['status' => SubscriptionStatus::Expiring]);
                $this->info("Subscription #{$subscription->id} ({$subscription->domain_name}) is now EXPIRING (days left: {$daysLeft}).");
            }

            // 3. Send reminders at the configured intervals — filtered down to
            // whatever actually fits this subscription's own billing cycle
            // (e.g. a 30-day reminder makes no sense for a monthly subscription).
            $applicableDays = $subscription->applicableReminderDays($configuredReminderDays);

            if ($daysLeft > 0 && in_array($daysLeft, $applicableDays, true)) {
                $this->notificationService->sendExpiryReminder($subscription);
                $notifiedCount++;
            }
        }

        $this->info("Done! Processed {$processedCount} subscriptions and sent {$notifiedCount} notifications.");
    }
}
