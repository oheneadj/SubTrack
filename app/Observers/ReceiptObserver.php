<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityEventType;
use App\Models\DashboardActivityLog;
use App\Models\Receipt;

/** Records receipt generation to the dashboard activity feed. */
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
}
