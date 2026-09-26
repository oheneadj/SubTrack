<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\ReceiptMail;
use App\Models\Receipt;
use App\Models\Subscription;
use App\Services\EmailLogger;
use App\Services\ReceiptNumberService;
use App\Services\ReceiptPdfService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Generates a receipt for a subscription payment (a purchase or a renewal),
 * renders it to PDF, and emails it to the subscription's client.
 *
 * The record + PDF path update is wrapped in a transaction since it's a
 * multi-step write that must succeed or fail together — a receipt without
 * a stored PDF path would be a broken record.
 */
class GenerateReceiptAction
{
    public function __construct(
        private readonly ReceiptNumberService $numberService,
        private readonly ReceiptPdfService $pdfService,
    ) {}

    /**
     * @throws \RuntimeException if the subscription has no linked client (effective client is null)
     */
    public function execute(Subscription $subscription, int $amountUsd, ?string $notes = null): Receipt
    {
        $client = $subscription->effective_client;

        if (! $client) {
            throw new \RuntimeException('Cannot generate a receipt for a subscription with no linked client.');
        }

        $receipt = DB::transaction(function () use ($subscription, $client, $amountUsd, $notes) {
            $receipt = Receipt::create([
                'subscription_id' => $subscription->id,
                'client_id' => $client->id,
                'receipt_number' => $this->numberService->generate(),
                'amount_usd' => $amountUsd,
                'issued_date' => CarbonImmutable::now(),
                'notes' => $notes,
            ]);

            $this->pdfService->generate($receipt);

            return $receipt;
        });

        $mail = new ReceiptMail($receipt->fresh());
        app(EmailLogger::class)->track(
            $mail,
            $client->email,
            $client->name,
            clientId: $client->id,
            context: ['receipt_id' => $receipt->id, 'subscription_id' => $subscription->id],
        );

        Mail::to($client->email)->queue($mail);

        return $receipt;
    }
}
