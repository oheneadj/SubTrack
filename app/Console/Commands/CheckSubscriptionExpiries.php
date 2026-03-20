<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
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

        $subscriptions = Subscription::where('status', '!=', SubscriptionStatus::Cancelled)->get();
        $processedCount = 0;
        $notifiedCount = 0;

        foreach ($subscriptions as $subscription) {
            $processedCount++;
            $daysLeft = $subscription->days_until_expiry;
            $oldStatus = $subscription->status;

            // 1. Update status if expired
            if ($subscription->expiry_date->isPast()) {
                if ($subscription->status !== SubscriptionStatus::Expired) {
                    $subscription->update(['status' => SubscriptionStatus::Expired]);
                    $this->warn("Subscription #{$subscription->id} ({$subscription->domain_name}) has EXPIRED.");
                    $this->notificationService->sendExpiryReminder($subscription);
                    $notifiedCount++;
                    continue;
                }
            } 
            
            // 2. Update status if expiring soon (<= 30 days)
            if ($daysLeft <= 30 && $daysLeft > 0) {
                if ($subscription->status === SubscriptionStatus::Active) {
                    $subscription->update(['status' => SubscriptionStatus::Expiring]);
                    $this->info("Subscription #{$subscription->id} ({$subscription->domain_name}) is now EXPIRING (days left: {$daysLeft}).");
                }

                // 3. Send reminders at specific intervals (30, 7, 3, 1 days)
                if (in_array($daysLeft, [30, 7, 3, 1])) {
                    $this->notificationService->sendExpiryReminder($subscription);
                    $notifiedCount++;
                }
            }
        }

        $this->info("Done! Processed {$processedCount} subscriptions and sent {$notifiedCount} notifications.");
    }
}
