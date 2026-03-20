<?php

namespace App\Enums;

enum ActivityEventType: string
{
    case SubscriptionExpiring  = 'subscription.expiring';
    case SubscriptionExpired   = 'subscription.expired';
    case SubscriptionCreated   = 'subscription.created';
    case ReminderSent          = 'reminder.sent';
    case InvoiceCreated        = 'invoice.created';
    case InvoiceSent           = 'invoice.sent';
    case InvoicePaid           = 'invoice.paid';
    case InvoiceOverdue        = 'invoice.overdue';
    case RenewalConfirmed      = 'renewal.confirmed';
    case ClientCreated         = 'client.created';
}
