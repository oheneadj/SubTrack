@php
    $isExpired = $subscription->days_until_expiry <= 0;
    $title = $isExpired ? 'Urgent: Service Expired' : 'Upcoming Service Expiry';
    $headerTxt = $isExpired ? 'URGENT NOTIFICATION' : 'RENEWAL REMINDER';
@endphp
<x-email.layout :title="$title" :headerText="$headerTxt">
    <h4 style="color: #0f172a; font-size: 18px; margin-top:0; margin-bottom: 24px;">Hello,</h4>
    
    @if($body)
        <div style="line-height: 1.7; color: #4b5563; margin-bottom: 32px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
            {!! nl2br(e($body)) !!}
        </div>
    @else
        <p style="line-height: 1.7; color: #4b5563; margin-bottom: 32px;">
            This is an automated notification regarding the active service attached to your project: <strong style="color: #1e293b;">{{ $subscription->project?->project_name ?? 'your project' }}</strong>.
        </p>
    @endif

    <div style="display: block; width: 100%; border: 1px solid #e2e8f0; border-radius: 8px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; padding: 20px;">
            <tr>
                <td style="padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #64748b;">
                    Service Name
                </td>
                <td align="right" style="padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #0f172a;">
                    {{ $subscription->domain_name ?: ($subscription->service_type?->label() ?? 'Service') }}
                </td>
            </tr>
            <tr>
                <td style="padding-top: 12px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #64748b;">
                    Provider
                </td>
                <td align="right" style="padding-top: 12px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #0f172a;">
                    {{ $subscription->provider?->name ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td style="padding-top: 12px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; color: #64748b;">
                    Expiry Date
                </td>
                <td align="right" style="padding-top: 12px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1; font-size: 15px; font-weight: 600; {{ $subscription->days_until_expiry <= 7 ? 'color: #ef4444;' : 'color: #0f172a;' }}">
                    {{ $subscription->expiry_date->format('F d, Y') }}
                </td>
            </tr>
            <tr>
                <td style="padding-top: 16px; font-size: 16px; font-weight: 800; color: #0f172a;">
                    Time Remaining
                </td>
                <td align="right" style="padding-top: 16px; font-size: 16px; font-weight: 800; {{ $isExpired ? 'color: #ef4444;' : 'color: #f97316;' }}">
                    @if($subscription->days_until_expiry < 0)
                        Expired {{ abs($subscription->days_until_expiry) }} days ago
                    @elseif($subscription->days_until_expiry == 0)
                        Expires TODAY
                    @else
                        {{ $subscription->days_until_expiry }} days remaining
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <p style="line-height: 1.6; color: #4b5563; margin-top: 32px;">
        To ensure continued service and avoid any potential downtime, please arrange for renewal as soon as possible.
    </p>
</x-email.layout>
