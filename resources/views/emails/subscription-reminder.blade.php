<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Service Renewal Reminder</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 20px; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f172a; color: #ffffff; padding: 30px; text-align: center; }
        .content { padding: 40px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; }
        .button { display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: bold; margin-bottom: 15px; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        .status-expiring { background: #ffedd5; color: #9a3412; }
        .details-box { background: #f1f5f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
        .detail-label { font-weight: bold; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">SubTrack</h1>
        </div>
        <div class="content">
            @if($subscription->days_until_expiry <= 0)
                <div class="status-badge status-expired">EXPIRED</div>
                <h2 style="color: #ef4444; margin-top: 0;">Urgent: Your service has expired</h2>
            @else
                <div class="status-badge status-expiring">EXPIRING SOON</div>
                <h2 style="margin-top: 0;">Reminder: Your service expiry is upcoming</h2>
            @endif

            @if($body)
                <div style="white-space: pre-line; margin-bottom: 20px;">
                    {!! nl2br(e($body)) !!}
                </div>
            @else
                <p>Hello,</p>
                <p>This is an automated notification regarding your service for <strong>{{ $subscription->project->project_name }}</strong>.</p>
            @endif

            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">Service:</span>
                    <span>{{ $subscription->domain_name ?: $subscription->service_type->label() }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Provider:</span>
                    <span>{{ $subscription->provider }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Expiry Date:</span>
                    <span style="{{ $subscription->days_until_expiry <= 7 ? 'color: #ef4444; font-weight: bold;' : '' }}">
                        {{ $subscription->expiry_date->format('F d, Y') }}
                    </span>
                </div>
                <div class="detail-row" style="border-bottom: none;">
                    <span class="detail-label">Time Remaining:</span>
                    <span>
                        @if($subscription->days_until_expiry < 0)
                            Expired {{ abs($subscription->days_until_expiry) }} days ago
                        @elseif($subscription->days_until_expiry == 0)
                            Expires TODAY
                        @else
                            {{ $subscription->days_until_expiry }} days remaining
                        @endif
                    </span>
                </div>
            </div>

            <p>To ensure continued service and avoid any potential downtime, please arrange for renewal as soon as possible.</p>
            
            <p>Thank you for choosing us.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SubTrack Management. All rights reserved.
        </div>
    </div>
</body>
</html>
