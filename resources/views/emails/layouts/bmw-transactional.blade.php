<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BMW Showroom')</title>
</head>
<body style="margin: 0; padding: 0; background-color: #09090b; color: #ffffff; font-family: 'Be Vietnam Pro', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #09090b; padding: 40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #121215; border: 1px solid #27272a; border-radius: 0px;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 32px 32px 24px 32px; border-bottom: 1px solid #27272a; background-color: #09090b;">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.35em; text-transform: uppercase; color: #1C69D4; margin-bottom: 6px;">
                                SHEER DRIVING PLEASURE
                            </div>
                            <div style="font-size: 22px; font-weight: 900; letter-spacing: 0.25em; text-transform: uppercase; color: #ffffff;">
                                BMW SHOWROOM
                            </div>
                        </td>
                    </tr>

                    {{-- Body Content --}}
                    <tr>
                        <td style="padding: 32px; color: #ffffff; font-size: 14px; line-height: 1.7;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 24px 32px; border-top: 1px solid #27272a; background-color: #09090b; color: #a1a1aa; font-size: 12px; line-height: 1.6;">
                            <div style="font-weight: 800; text-transform: uppercase; letter-spacing: 0.2em; color: #ffffff; margin-bottom: 8px;">
                                BMW SHOWROOM VIỆT NAM
                            </div>
                            <div style="margin-bottom: 4px;">
                                <strong style="color: #ffffff;">Hotline 24/7:</strong> 1800-BMW-SERIES
                            </div>
                            <div style="margin-bottom: 4px;">
                                <strong style="color: #ffffff;">Showroom Hà Nội:</strong> 268 Đội Cấn, Ba Đình, Hà Nội
                            </div>
                            <div style="margin-bottom: 12px;">
                                <strong style="color: #ffffff;">Showroom TP. HCM:</strong> 800 BMW Way, Quận 7, TP. HCM
                            </div>
                            <div style="font-size: 11px; color: #71717a;">
                                Email này được gửi tự động từ hệ thống BMW Showroom.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
