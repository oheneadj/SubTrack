<x-email.layout title="You're Invited" headerText="ACCOUNT INVITATION">
    @if($body)
        <div style="color: #334155; font-size: 16px; line-height: 1.7; margin-bottom: 32px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
            {!! nl2br(e($body)) !!}
        </div>
    @else
        <h4 style="color: #0f172a; font-size: 18px; margin-top:0; margin-bottom: 20px;">Hi {{ $userName }},</h4>
        <p style="color: #4b5563; font-size: 16px; line-height: 1.7; margin-bottom: 32px;">
            You've been invited to join the <strong>{{ config('app.name') }}</strong> platform! In order to access your new account, please use the temporary login credentials provided below:
        </p>
    @endif

    <div style="margin-bottom: 36px;">
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: center;">
            <div style="margin-bottom: 20px;">
                <span style="color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em; display: block;">Email Address</span>
                <span style="color: #0f172a; font-size: 18px; font-weight: 700; display: block; margin-top: 6px;">{{ $userEmail }}</span>
            </div>
            <div>
                <span style="color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.1em; display: block;">Temporary Password</span>
                <span style="color: #2563eb; font-size: 22px; font-weight: 800; font-family: monospace; letter-spacing: 0.15em; display: block; margin-top: 6px;">{{ $plainPassword }}</span>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-bottom: 32px;">
        <a href="{{ $loginUrl }}" style="display: inline-block; padding: 14px 32px; font-size: 16px; font-weight: bold; background-color: #2563eb; color: #ffffff; border-radius: 8px; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
            Log In Now
        </a>
    </div>

    <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0; border-top: 1px solid #e2e8f0; padding-top: 24px;">
        For your baseline security, please change your password immediately after your first login into the port. If you did not expect this invitation, you can safely ignore and delete this email.
    </p>
</x-email.layout>
