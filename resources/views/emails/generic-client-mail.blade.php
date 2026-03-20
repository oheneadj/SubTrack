<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 20px; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f172a; color: #ffffff; padding: 30px; text-align: center; }
        .content { padding: 40px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">{{ config('app.name') }}</h1>
        </div>
        <div class="content">
            <div style="white-space: pre-line;">
                {!! nl2br(e($body)) !!}
            </div>

            <p style="margin-top: 30px;">Best regards,<br>{{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
