@php($font = "font-family:Montserrat,'Segoe UI',Helvetica,Arial,sans-serif;")
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>New enquiry</title></head>
<body style="margin:0;padding:0;background:#e8ddc8;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e8ddc8;padding:32px 12px;"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#f4eee3;border-radius:6px;overflow:hidden;">
    <tr><td style="background:#0b1a24;padding:28px 32px;border-bottom:3px solid #c9a25b;">
        <div style="{{ $font }}font-size:20px;font-weight:700;letter-spacing:.06em;color:#f4eee3;">{{ $company }}</div>
        <div style="{{ $font }}margin-top:18px;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#c9a25b;font-weight:600;">New website enquiry</div>
        <div style="{{ $font }}margin-top:6px;font-size:26px;line-height:1.15;font-weight:700;color:#f4eee3;">{{ $leadName }}</div>
        <div style="{{ $font }}margin-top:6px;font-size:13px;color:#b8b2a7;">{{ $received }}</div>
    </td></tr>
    <tr><td style="padding:24px 32px 4px;">
        <a href="tel:{{ $phone }}" style="{{ $font }}display:inline-block;background:#c9a25b;color:#0b1a24;text-decoration:none;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;padding:13px 22px;border-radius:999px;margin:0 8px 8px 0;">Call {{ $phone }}</a>
        <a href="https://wa.me/{{ ltrim($phone, '+') }}" style="{{ $font }}display:inline-block;background:#0b1a24;color:#f4eee3;text-decoration:none;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;padding:13px 22px;border-radius:999px;margin:0 0 8px;">WhatsApp</a>
    </td></tr>
    <tr><td style="padding:8px 32px 28px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        @foreach ($rows as $label => $value)
            <tr>
                <td style="{{ $font }}padding:12px 0;border-bottom:1px solid #e8ddc8;width:38%;vertical-align:top;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#7a5a1c;font-weight:600;">{{ $label }}</td>
                <td style="{{ $font }}padding:12px 0;border-bottom:1px solid #e8ddc8;font-size:15px;line-height:1.55;color:#0b1a24;">{!! nl2br(e($value)) !!}</td>
            </tr>
        @endforeach
    </table></td></tr>
    @if ($source)
        <tr><td style="{{ $font }}padding:0 32px 28px;font-size:12px;line-height:1.7;color:#5c636b;">
            @foreach ($source as $label => $value)
                {{ $label }}: @if ($label === 'Page')<a href="{{ $value }}" style="color:#7a5a1c;">{{ $value }}</a>@else{{ $value }}@endif<br>
            @endforeach
        </td></tr>
    @endif
    <tr><td style="{{ $font }}background:#0b1a24;padding:18px 32px;font-size:12px;color:#b8b2a7;">Sent from the enquiry form on the {{ $company }} website. The visitor agreed to be contacted about this enquiry.</td></tr>
</table>
</td></tr></table>
</body>
</html>
