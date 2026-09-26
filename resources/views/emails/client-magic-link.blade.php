<x-email.layout title="Login Link" headerText="CLIENT PORTAL">
    <h4 style="color: #0f172a; font-size: 18px; margin-top:0; margin-bottom: 24px;">Hello {{ $client->name }},</h4>

    <p style="line-height: 1.7; color: #4b5563; margin-bottom: 32px;">
        You requested a login link for your client portal. Click the button below to sign in and view your invoices. This link is single-use and expires in <strong>24 hours</strong>.
    </p>

    <div style="text-align: center; margin-top: 32px; margin-bottom: 8px;">
        <a href="{{ $loginUrl }}"
           style="display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 36px; border-radius: 8px; letter-spacing: 0.01em;">
            Sign In to Portal →
        </a>
    </div>

    <p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 12px;">
        If you didn't request this, you can safely ignore this email.
    </p>

    <p style="line-height: 1.6; color: #64748b; font-size: 13px; margin-top: 32px; word-break: break-all;">
        Or copy this link into your browser:<br>
        <a href="{{ $loginUrl }}" style="color: #2563eb;">{{ $loginUrl }}</a>
    </p>
</x-email.layout>
