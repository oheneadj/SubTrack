<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 13px; color: #1e293b; margin: 0; padding: 0; line-height: 1.5; }
        .p-10 { padding: 40px; }
        .header { background: #0f172a; color: #ffffff; padding: 40px; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-sm { font-size: 11px; }
        .text-xs { font-size: 10px; }
        .text-slate-500 { color: #64748b; }
        .mt-8 { margin-top: 32px; }
        .mb-2 { margin-bottom: 8px; }
        .border-b { border-bottom: 1px solid #e2e8f0; }
        .py-4 { padding-top: 16px; padding-bottom: 16px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 32px; }
        th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
        
        .totals { margin-top: 32px; float: right; width: 300px; }
        .total-row { display: block; border-bottom: 1px solid #f1f5f9; padding: 8px 0; }
        .total-label { color: #64748b; }
        .grand-total { font-size: 18px; font-bold: true; color: #3b82f6; border-bottom: none; }
        
        .footer { position: fixed; bottom: 40px; left: 40px; right: 40px; text-align: center; color: #94a3b8; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="background: transparent; margin-top: 0;">
            <tr>
                <td style="border: none; padding: 0;">
                    <h1 style="margin: 0; color: #ffffff; font-size: 28px;">INVOICE</h1>
                    <div style="color: #94a3b8;">{{ $invoice->invoice_number }}</div>
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
                    <div class="text-xs text-slate-500 font-bold uppercase mb-2">Billed To</div>
                    <div class="font-bold" style="font-size: 16px;">{{ $invoice->client->name }}</div>
                    <div class="text-slate-500">{{ $invoice->client->company_name }}</div>
                    <div class="text-slate-500">{{ $invoice->client->email }}</div>
                </td>
                <td style="border: none; padding: 0; text-align: right; width: 50%;">
                    <div class="mb-4">
                        <div class="text-xs text-slate-500 font-bold uppercase">Invoice Date</div>
                        <div class="font-bold">{{ $invoice->issued_date->format('F d, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-bold uppercase">Due Date</div>
                        <div class="font-bold" style="color: #ef4444;">{{ $invoice->due_date->format('F d, Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            <div class="font-bold">{{ $item->description }}</div>
                        </td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right font-bold">${{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span style="float: right;">${{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->tax_amount > 0)
                <div class="total-row">
                    <span class="total-label">Tax:</span>
                    <span style="float: right;">${{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
            @endif
            <div class="total-row grand-total">
                <span style="color: #1e293b;">Total:</span>
                <span style="float: right;">${{ number_format($invoice->total_amount, 2) }}</span>
            </div>
        </div>

        <div style="clear: both; margin-top: 60px;">
            <div class="text-xs text-slate-500 font-bold uppercase mb-2">Notes & Instructions</div>
            <div class="text-slate-500">{{ $invoice->notes ?: 'Thank you for your business!' }}</div>
        </div>
    </div>

    <div class="footer">
        {{ $settings['company_name'] ?? config('app.name') }} &bull; {{ $settings['company_email'] ?? 'support@example.com' }}
    </div>
</body>
</html>
