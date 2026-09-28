<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 13px; color: #1e293b; margin: 0; padding: 0; line-height: 1.5; }
        .p-10 { padding: 40px; }
        .header { background: #0f172a; color: #ffffff; padding: 40px; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-sm { font-size: 11px; }
        .text-xs { font-size: 10px; }
        .text-slate-500 { color: #64748b; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }

        table { width: 100%; border-collapse: collapse; margin-top: 32px; }
        th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; }

        .amount-box { margin-top: 32px; text-align: right; }
        .grand-total { font-size: 22px; font-weight: bold; color: #16a34a; }

        .footer { position: fixed; bottom: 40px; left: 40px; right: 40px; text-align: center; color: #94a3b8; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="background: transparent; margin-top: 0;">
            <tr>
                <td style="border: none; padding: 0;">
                    <h1 style="margin: 0; color: #ffffff; font-size: 28px;">RECEIPT</h1>
                    <div style="color: #94a3b8;">{{ $receipt->receipt_number }}</div>
                </td>
                <td style="border: none; padding: 0; text-align: right;">
                    <div class="font-bold" style="font-size: 18px;">{{ config('app.name', 'SubTrack') }}</div>
                    <div class="text-sm" style="color: #94a3b8;">{{ $settings['company_address'] ?? 'Your Address Here' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="p-10">
        <table style="margin-top: 0; background: transparent;">
            <tr>
                <td style="border: none; padding: 0; width: 50%;">
                    <div class="text-xs text-slate-500 font-bold" style="text-transform: uppercase;">Received From</div>
                    <div class="font-bold" style="font-size: 16px;">{{ $receipt->client->name }}</div>
                    <div class="text-slate-500">{{ $receipt->client->email }}</div>
                </td>
                <td style="border: none; padding: 0; text-align: right; width: 50%;">
                    <div class="text-xs text-slate-500 font-bold" style="text-transform: uppercase;">Issued</div>
                    <div class="font-bold">{{ $receipt->issued_date->format('F d, Y') }}</div>
                </td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="font-bold">{{ $receipt->source_label }}</div>
                        @if($receipt->subscription)
                            <div class="text-xs text-slate-500">{{ $receipt->subscription->renewal_type->label() }}</div>
                        @endif
                    </td>
                    <td class="text-right font-bold">{{ $receipt->formatted_amount_usd }}</td>
                </tr>
            </tbody>
        </table>

        <div class="amount-box">
            <div class="text-xs text-slate-500" style="text-transform: uppercase;">Total Paid</div>
            <div class="grand-total">{{ $receipt->formatted_amount_usd }}</div>
        </div>

        @if($receipt->notes)
            <div style="clear: both; margin-top: 60px;">
                <div class="text-xs text-slate-500 font-bold mb-2" style="text-transform: uppercase;">Notes</div>
                <div class="text-slate-500">{{ $receipt->notes }}</div>
            </div>
        @endif
    </div>

    <div class="footer">
        {{ $settings['company_name'] ?? config('app.name') }} &bull; {{ $settings['company_email'] ?? 'support@example.com' }}
    </div>
</body>
</html>
