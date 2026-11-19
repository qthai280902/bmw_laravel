<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>@yield('title', 'BMW Showroom Vietnam — Concierge & Client Relations')</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0B0E14; color: #E2E8F0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    @php
        $accentColor = trim($__env->yieldContent('accent_color', '#1C69D4'));
        $headerSubtitleColor = trim($__env->yieldContent('header_subtitle_color', '#60A5FA'));
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0B0E14; padding: 36px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
        <tr>
            <td align="center" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #121620; border: 1px solid #222938; border-top: 4px solid {{ $accentColor }}; border-radius: 0px;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 24px 32px; background-color: #0E121B; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td valign="middle" style="padding-right: 14px;">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="38" height="38" style="width: 38px; height: 38px; border: 2px solid {{ $accentColor }}; background-color: #0B0E14;">
                                                        <tr>
                                                            <td align="center" valign="middle" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 1px; color: #F8FAFC;">
                                                                BMW
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="middle" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                                                    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 2.5px; color: #F8FAFC; text-transform: uppercase;">
                                                        BMW SHOWROOM VIETNAM
                                                    </div>
                                                    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 2px; color: {{ $headerSubtitleColor }}; text-transform: uppercase; margin-top: 3px;">
                                                        CONCIERGE &amp; CLIENT RELATIONS
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td align="right" valign="middle" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                                        @yield('badge')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #CBD5E1; font-size: 14px; line-height: 1.65;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 22px 32px; background-color: #0E121B; border-top: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                            <p style="margin: 0 0 8px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #8E9BAE; line-height: 1.6;">
                                <strong style="color: #F8FAFC; font-weight: 600;">BMW Showroom Flagship</strong> &mdash;
                                <span style="color: #F8FAFC; text-decoration: none;">123 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh</span>
                            </p>
                            <p style="margin: 0 0 8px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #8E9BAE; line-height: 1.6;">
                                Hotline Concierge:
                                <a href="tel:1900123456" style="color: #F8FAFC; text-decoration: none; font-weight: 600;">1900 123 456</a>
                                &nbsp;&bull;&nbsp;
                                Giờ mở cửa: <span style="color: #F8FAFC; text-decoration: none;">08:00 &ndash; 20:00 (Thứ Hai &ndash; Chủ Nhật)</span>
                            </p>
                            <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #64748B; line-height: 1.5;">
                                &copy; {{ date('Y') }} BMW Showroom Vietnam. Sheer Driving Pleasure.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
