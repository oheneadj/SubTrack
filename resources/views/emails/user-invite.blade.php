<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Invited</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0;">
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); padding: 32px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;">Welcome to {{ config('app.name') }}</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 40px;">
                            @if($body)
                                <div style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 24px; white-space: pre-line;">
                                    {!! nl2br(e($body)) !!}
                                </div>
                            @else
                                <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                    Hi <strong>{{ $userName }}</strong>,
                                </p>
                                <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 24px;">
                                    You've been invited to join <strong>{{ config('app.name') }}</strong>. Here are your login credentials:
                                </p>
                            @endif

                            {{-- Credentials Box --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; border-radius: 12px; margin: 0 0 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Email</span><br>
                                                    <span style="color: #1e293b; font-size: 16px; font-weight: 600;">{{ $userEmail }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Password</span><br>
                                                    <span style="color: #1e293b; font-size: 16px; font-weight: 600; font-family: monospace; letter-spacing: 0.1em;">{{ $plainPassword }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $loginUrl }}" style="display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 600; font-size: 16px;">
                                            Log In Now
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                                For security, please change your password after your first login. If you did not expect this invitation, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
