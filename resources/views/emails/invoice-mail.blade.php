<x-email.layout :title="'Invoice: ' . $invoice->invoice_number" headerText="BILLING & INVOICING">
    <h4 style="color: #0f172a; font-size: 18px; margin-top:0; margin-bottom: 24px;">Hello {{ $invoice->client->name }},</h4>
    
    @if($body)
        <div style="line-height: 1.7; color: #4b5563; margin-bottom: 32px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
            {!! nl2br(e($body)) !!}
        </div>
    @else
        <p style="line-height: 1.7; color: #4b5563; margin-bottom: 32px;">
            Please find attached the latest invoice for your project: <strong style="color: #1e293b;">{{ $invoice->project->project_name }}</strong>.
        </p>
    @endif

    <!-- Data Details Box -->
    <div style="display: block; width: 100%; border: 1px solid #e2e8f0; border-radius: 8px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; padding: 20px;">
            <tr>
                <td style="padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #64748b;">
                    Invoice Number
                </td>
                <td align="right" style="padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #0f172a;">
                    {{ $invoice->invoice_number }}
                </td>
            </tr>
            <tr>
                <td style="padding-top: 12px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #64748b;">
                    Due Date
                </td>
                <td align="right" style="padding-top: 12px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; {{ $invoice->due_date->isPast() ? 'color: #ef4444;' : 'color: #0f172a;' }}">
                    {{ $invoice->due_date->format('F d, Y') }}
                </td>
            </tr>
            <tr>
                <td style="padding-top: 16px; font-size: 18px; font-weight: 800; color: #0f172a;">
                    Total Amount
                </td>
                <td align="right" style="padding-top: 16px; font-size: 18px; font-weight: 800; color: #2563eb;">
                    ${{ number_format($invoice->total_amount, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <p style="line-height: 1.6; color: #64748b; font-size: 14px; margin-top: 32px;">
        You can find the full breakdown in the attached PDF file. If you have any questions regarding this invoice, please don't hesitate to reach out to us.
    </p>
</x-email.layout>
