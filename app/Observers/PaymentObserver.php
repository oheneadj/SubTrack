<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityEventType;
use App\Enums\PaymentRecordStatus;
use App\Models\DashboardActivityLog;
use App\Models\Payment;

/** Records manual payment recording, editing, and voiding to the dashboard activity feed. */
class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if ($payment->status !== PaymentRecordStatus::Succeeded || $payment->gateway !== 'manual') {
            return;
        }

        DashboardActivityLog::record(
            ActivityEventType::PaymentRecorded,
            "Payment of {$payment->formatted_amount} recorded manually for Invoice {$payment->invoice->invoice_number}",
            $payment->invoice->client_id,
            ['payment_id' => $payment->id, 'invoice_id' => $payment->invoice_id]
        );
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('status') && $payment->status === PaymentRecordStatus::Voided) {
            DashboardActivityLog::record(
                ActivityEventType::PaymentVoided,
                "Payment of {$payment->formatted_amount} voided for Invoice {$payment->invoice->invoice_number}".
                    ($payment->void_reason ? " — {$payment->void_reason}" : ''),
                $payment->invoice->client_id,
                ['payment_id' => $payment->id, 'invoice_id' => $payment->invoice_id]
            );

            return;
        }

        if ($payment->wasChanged('amount') && $payment->status === PaymentRecordStatus::Succeeded) {
            $original = $payment->getOriginal('amount');
            DashboardActivityLog::record(
                ActivityEventType::PaymentEdited,
                "Payment for Invoice {$payment->invoice->invoice_number} corrected from \$".number_format($original / 100, 2).
                    " to {$payment->formatted_amount}",
                $payment->invoice->client_id,
                ['payment_id' => $payment->id, 'invoice_id' => $payment->invoice_id, 'previous_amount' => $original]
            );
        }
    }
}
