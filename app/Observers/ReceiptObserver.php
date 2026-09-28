<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityEventType;
use App\Models\DashboardActivityLog;
use App\Models\Receipt;

/** Records receipt generation and invalidation to the dashboard activity feed. */
class ReceiptObserver
{
    /** Log a receipt being generated. */
    public function created(Receipt $receipt): void
    {
        DashboardActivityLog::record(
            ActivityEventType::ReceiptGenerated,
            "Receipt {$receipt->receipt_number} generated ({$receipt->formatted_amount_usd})",
            $receipt->client_id,
            ['receipt_id' => $receipt->id, 'subscription_id' => $receipt->subscription_id]
        );
    }

    /** Log a receipt being invalidated (its underlying payment was voided). */
    public function updated(Receipt $receipt): void
    {
        if (! $receipt->wasChanged('invalidated_at') || ! $receipt->invalidated_at) {
            return;
        }

        DashboardActivityLog::record(
            ActivityEventType::ReceiptInvalidated,
            "Receipt {$receipt->receipt_number} invalidated — {$receipt->invalidated_reason}",
            $receipt->client_id,
            ['receipt_id' => $receipt->id]
        );
    }
}
