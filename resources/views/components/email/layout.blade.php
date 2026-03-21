@props(['title', 'headerText' => 'NOTIFICATION', 'bannerIcon' => null])
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $title ?? config('app.name') }}</title>
  <style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap');
    body, table, td, a { font-family: 'Outfit', Helvetica, Arial, sans-serif !important; }
  </style>
</head>
<body style="background-color: #f8fafc; padding: 40px 10px; font-family: 'Outfit', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
  <center style="width: 100%; table-layout: fixed;">
    <table width="100%" bgcolor="#ffffff" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; margin: 0 auto; border: 1px solid #e2e8f0; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
      
      <!-- Top Logo Header -->
      <tr>
        <td align="center" style="padding: 24px; background-color: #ffffff; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
          @php
              $brandingName = \App\Models\Setting::get('business_name') ?: \App\Models\Setting::get('app_name') ?: config('app.name', 'SubTrack');
          @endphp
          <span style="font-size: 26px; font-weight: 800; color: #1e293b; letter-spacing: -0.5px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">{{ $brandingName }}</span>
        </td>
      </tr>

      <!-- Primary Blue Banner Area -->
      <tr>
        <td align="center" style="background-color: #2563eb; padding: 40px 20px; color: #ffffff; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
          <table width="100%" style="font-family: 'Outfit', Helvetica, Arial, sans-serif;">
            <tr>
              <td align="center" style="padding-bottom: 16px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
                @if($bannerIcon)
                   {!! $bannerIcon !!}
                @else
                   <span style="font-size: 24px; display:inline-block;">&#9993;</span>
                @endif
              </td>
            </tr>
            <tr>
              <td align="center" style="font-size: 13px; font-weight: 600; letter-spacing: 1.5px; opacity: 0.9; color: #ffffff; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
                {{ strtoupper($headerText) }}
              </td>
            </tr>
            <tr>
              <td align="center" style="font-size: 24px; font-weight: 800; padding-top: 8px; color: #ffffff; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
                {{ $title }}
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Main Content Slot -->
      <tr>
        <td style="padding: 30px 32px; color: #334155; font-size: 16px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
          {{ $slot }}
          <p style="margin-top: 20px; color: #4b5563; font-size: 15px; line-height: 1.6; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
            Thank you,<br />
            <strong style="color: #1e293b; font-family: 'Outfit', Helvetica, Arial, sans-serif;">The {{ $brandingName }} Team</strong>
          </p>
        </td>
      </tr>

      <!-- Warning Footer -->
      <tr>
        <td align="center" style="padding: 10px 32px; color: #94a3b8; font-size: 13px; line-height: 1.6; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
          This email was sent automatically.
        </td>
      </tr>

      <!-- Clean Footer Block -->
      <tr>
        <td align="center" style="background-color: #f1f5f9; padding: 32px; margin-top: 32px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
          @php
              $contactEmail = \App\Models\Setting::get('contact_email') ?: \App\Models\Setting::get('business_email');
              $contactPhone = \App\Models\Setting::get('business_phone');
          @endphp
          <h4 style="color: #1e293b; font-weight: 700; font-size: 16px; margin: 0 0 8px 0; font-family: 'Outfit', Helvetica, Arial, sans-serif;">Get in touch</h4>
          @if($contactPhone)
            <a href="tel:{{ $contactPhone }}" style="color: #4b5563; text-decoration: none; font-weight: 500; display: block; margin-bottom: 4px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">{{ $contactPhone }}</a>
          @endif
          @if($contactEmail)
            <a href="mailto:{{ $contactEmail }}" style="color: #4b5563; text-decoration: none; font-weight: 500; display: block; font-family: 'Outfit', Helvetica, Arial, sans-serif;">{{ $contactEmail }}</a>
          @endif
        </td>
      </tr>

      <!-- Copyright -->
      <tr>
        <td align="center" style="background-color: #0f172a; padding: 20px; color: #94a3b8; font-size: 13px; font-family: 'Outfit', Helvetica, Arial, sans-serif;">
          &copy; {{ date('Y') }} {{ $brandingName }}. All Rights Reserved.
        </td>
      </tr>

    </table>
  </center>
</body>
</html>
