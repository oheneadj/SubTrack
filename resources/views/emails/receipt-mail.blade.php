<x-email.layout :title="'Receipt: ' . $receipt->receipt_number" headerText="PAYMENT RECEIPT">
    <h4 style="color: #0f172a; font-size: 18px; margin-top:0; margin-bottom: 24px;">Hello {{ $receipt->client->name }},</h4>

    <p style="line-height: 1.7; color: #4b5563; margin-bottom: 32px;">
        Thank you for your payment. Please find your receipt attached for
        <strong style="color: #1e293b;">{{ $receipt->source_label }}</strong>.
    </p>

    <div style="display: block; width: 100%; border: 1px solid #e2e8f0; border-radius: 8px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; padding: 20px;">
            <tr>
                <td style="padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #64748b;">
                    Receipt Number
                </td>
                <td align="right" style="padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #0f172a;">
                    {{ $receipt->receipt_number }}
                </td>
            </tr>
            <tr>
                <td style="padding-top: 12px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #64748b;">
                    Issued
                </td>
                <td align="right" style="padding-top: 12px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #0f172a;">
                    {{ $receipt->issued_date->format('F d, Y') }}
                </td>
            </tr>
            <tr>
                <td style="padding-top: 16px; font-size: 18px; font-weight: 800; color: #0f172a;">
                    Amount Paid
                </td>
                <td align="right" style="padding-top: 16px; font-size: 18px; font-weight: 800; color: #16a34a;">
                    {{ $receipt->formatted_amount_usd }}
                </td>
            </tr>
        </table>
    </div>

    <p style="line-height: 1.6; color: #64748b; font-size: 14px; margin-top: 32px;">
        The full receipt is attached as a PDF. If you have any questions, please don't hesitate to reach out to us.
    </p>
</x-email.layout>
