<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice from {{ config('app.name') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 20px; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f172a; color: #ffffff; padding: 30px; text-align: center; }
        .content { padding: 40px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; }
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
            <h2 style="margin-top: 0;">New Invoice: {{ $invoice->invoice_number }}</h2>
            
            @if($body)
                <div style="white-space: pre-line; margin-bottom: 20px;">
                    {!! nl2br(e($body)) !!}
                </div>
            @else
                <p>Hello {{ $invoice->client->name }},</p>
                <p>Please find attached the invoice for your project: <strong>{{ $invoice->project->project_name }}</strong>.</p>
            @endif

            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">Invoice Number:</span>
                    <span>{{ $invoice->invoice_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Due Date:</span>
                    <span style="{{ $invoice->due_date->isPast() ? 'color: #ef4444; font-weight: bold;' : '' }}">
                        {{ $invoice->due_date->format('F d, Y') }}
                    </span>
                </div>
                <div class="detail-row" style="border-bottom: none;">
                    <span class="detail-label">Total Amount:</span>
                    <span class="font-bold">${{ number_format($invoice->total_amount, 2) }}</span>
                </div>
            </div>

            <p>You can find the full breakdown in the attached PDF file.</p>
            
            <p>If you have any questions regarding this invoice, please don't hesitate to reach out.</p>
            
            <p>Best regards,<br>{{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
