<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title', 'BMW Showroom Vietnam — Concierge & Client Relations')</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F1F5F9; color: #0F172A; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    @php
        $accentColor = trim($__env->yieldContent('accent_color', '#1C69D4'));
        $headerSubtitle = trim($__env->yieldContent('header_subtitle', 'Concierge & Client Relations'));
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F1F5F9; padding: 36px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
        <tr>
            <td align="center" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);">
                    {{-- Top 4px Accent Bar --}}
                    <tr>
                        <td style="height: 4px; background-color: {{ $accentColor }}; font-size: 0; line-height: 0;">&nbsp;</td>
                    </tr>

                    {{-- Minimalist Luxury Header --}}
                    <tr>
                        <td style="padding: 22px 32px; background-color: #FFFFFF; border-bottom: 1px solid #E2E8F0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 800; letter-spacing: 1.8px; color: #0F172A; text-transform: uppercase;">
                                            BMW SHOWROOM
                                        </div>
                                        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 500; color: #64748B; margin-top: 2px;">
                                            {{ $headerSubtitle }}
                                        </div>
                                    </td>
                                    <td align="right" valign="middle" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                        @yield('badge')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Main Body --}}
                    <tr>
                        <td style="padding: 32px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #475569; font-size: 15px; line-height: 1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Clean 3-Line Footer --}}
                    <tr>
                        <td style="padding: 20px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                            <p style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; color: #475569; line-height: 1.55;">
                                <strong style="color: #0F172A; font-weight: 600;">BMW Showroom Flagship</strong> &mdash; 123 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh
                            </p>
                            <p style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; color: #64748B; line-height: 1.55;">
                                Hotline: <a href="tel:1900123456" style="color: #0F172A; text-decoration: none; font-weight: 600;">1900 123 456</a> &bull;
                                @if($__env->hasSection('hide_reply_hint'))
                                    Đây là email thông báo tự động một chiều, vui lòng không phản hồi trực tiếp thư này.
                                @else
                                    Quý khách có thể bấm <strong>Trả lời (Reply)</strong> trực tiếp email này để trao đổi với Chuyên viên.
                                @endif
                            </p>
                            <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; color: #94A3B8; line-height: 1.5;">
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
