<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityEventType: string
{
    case SubscriptionExpiring = 'subscription.expiring';
    case SubscriptionExpired = 'subscription.expired';
    case SubscriptionAutoCancelled = 'subscription.auto_cancelled';
    case SubscriptionCreated = 'subscription.created';
    case ReminderSent = 'reminder.sent';
    case InvoiceCreated = 'invoice.created';
    case InvoiceSent = 'invoice.sent';
    case InvoicePaid = 'invoice.paid';
    case InvoiceOverdue = 'invoice.overdue';
    case RenewalConfirmed = 'renewal.confirmed';
    case ClientCreated = 'client.created';
    case ReceiptGenerated = 'receipt.generated';
    case ReceiptInvalidated = 'receipt.invalidated';
    case PaymentRecorded = 'payment.recorded';
    case PaymentEdited = 'payment.edited';
    case PaymentVoided = 'payment.voided';
}
