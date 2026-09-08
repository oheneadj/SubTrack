<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentRecordStatus;
use App\Models\Payment;
use App\Services\Payment\GatewayRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Active polling fallback for payments whose webhook confirmation never arrived.
 *
 * Webhooks can be delivered late or dropped entirely by the provider, so this
 * scheduled command directly asks each payment's gateway for its current
 * status once it's been pending longer than a short grace period.
 */
class PollPendingPayments extends Command
{
    /** @var string */
    protected $signature = 'payments:poll-pending';

    /** @var string */
    protected $description = 'Poll gateways directly for payments still pending past the webhook grace period';

    private const GRACE_PERIOD_MINUTES = 15;

    /** Execute the console command. */
    public function handle(GatewayRegistry $registry): void
    {
        $cutoff = CarbonImmutable::now()->subMinutes(self::GRACE_PERIOD_MINUTES);

        $pending = Payment::where('status', PaymentRecordStatus::Pending)
            ->whereNotNull('gateway_payment_id')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $this->info("Polling {$pending->count()} pending payment(s)...");

        foreach ($pending as $payment) {
            $registry->get($payment->gateway)->pollStatus($payment);
        }

        $this->info('Done.');
    }
}
