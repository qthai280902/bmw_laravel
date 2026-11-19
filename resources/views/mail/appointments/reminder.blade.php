@extends('emails.layouts.bmw-concierge')

@section('title', 'Nhắc Lịch Hẹn 24h | BMW Showroom Vietnam')
@section('accent_color', '#6366F1')
@section('header_subtitle', 'AUTOMATED 24-HOUR REMINDER')
@section('header_subtitle_color', '#A5B4FC')

@section('badge')
    <span style="display: inline-block; padding: 5px 12px; background-color: #1E1B4B; border: 1px solid #6366F1; color: #C7D2FE; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase;">
        NHẮC LỊCH TỰ ĐỘNG &middot; CÒN 24 GIỜ
    </span>
@endsection

@section('content')
    @php
        $car = $car ?? $appointment->product;
        $resolvedInlinePath = $inlineImagePath ?? $car?->localImagePath();
        $resolvedFallbackPng = $fallbackBadgePng ?? \App\Mail\Customer\AppointmentConfirmationMail::fallbackBadgePngBytes();
        $inlineSrc = null;
        if (isset($message) && method_exists($message, 'embed') && method_exists($message, 'embedData')) {
            if (is_string($resolvedInlinePath) && $resolvedInlinePath !== '' && is_file($resolvedInlinePath)) {
                $inlineSrc = $message->embed($resolvedInlinePath);
            } else {
                $inlineSrc = $message->embedData($resolvedFallbackPng, 'bmw-showroom-badge.png', 'image/png');
            }
        }
    @endphp
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 2px; color: #A5B4FC; text-transform: uppercase; margin-bottom: 8px;">
        ĐẾM NGƯỢC 24 GIỜ TỚI LỊCH HẸN TẠI SHOWROOM
    </div>
    <h1 style="margin: 0 0 18px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 20px; font-weight: 700; color: #F8FAFC; line-height: 1.35;">
        Kính chào Quý khách {{ $appointment->customer_name }},
    </h1>

    {{-- Khối đồng hồ đếm ngược 24h & Giấy tờ cần mang theo --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #14132C; border: 1px solid #4F46E5; border-left: 4px solid #6366F1; margin-bottom: 22px;">
        <tr>
            <td style="padding: 16px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.8px; color: #A5B4FC; text-transform: uppercase; margin-bottom: 6px;">
                    CÒN KHOẢNG 24 GIỜ NỮA &mdash; CHUẨN BỊ TRƯỚC KHI ĐẾN SHOWROOM
                </div>
                <p style="margin: 0 0 8px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #FFFFFF;">
                    Khung giờ hẹn: {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
                </p>
                <p style="margin: 0 0 4px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #E0E7FF; line-height: 1.6;">
                    &bull; <strong>Giấy tờ cần mang theo:</strong> CMND/CCCD còn hiệu lực; <strong>Bằng lái xe hạng B còn hạn</strong> (bắt buộc nếu Quý khách tham gia lái thử).
                </p>
                <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #E0E7FF; line-height: 1.6;">
                    &bull; <strong>Lộ trình di chuyển:</strong> BMW Showroom Flagship &mdash; 123 Nguyễn Văn Linh, Quận 7, TP.HCM (vui lòng đến trước 10 phút để làm thủ tục đón tiếp VIP).
                </p>
            </td>
        </tr>
    </table>

    @if($inlineSrc)
        <div style="margin: 0 0 20px 0; border: 1px solid #1E2533; background-color: #0E121B;">
            <img src="{{ $inlineSrc }}" alt="{{ $car ? $car->name : 'BMW Showroom Vietnam' }}" style="display: block; width: 100%; max-height: 220px; object-fit: cover; border: 0;" />
        </div>
    @endif

    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #CBD5E1; line-height: 1.65;">
        BMW Showroom Vietnam xin trân trọng nhắc lịch hẹn <strong style="color: #F8FAFC; font-weight: 600;">{{ $appointment->typeLabel() }}</strong> của Quý khách sẽ diễn ra trong vòng 24 giờ tới. Đội ngũ BMW Genius và khoang dịch vụ đã sẵn sàng đón tiếp Quý khách.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0E121B; border: 1px solid #1E2533; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px; width: 38%;">
                Mã Lịch Hẹn
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #60A5FA;">
                #BMW-{{ str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT) }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Hạng Mục
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                {{ $appointment->typeLabel() }}
            </td>
        </tr>
        @if($appointment->servicePackageLabel())
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                    Gói Dịch Vụ
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #60A5FA;">
                    {{ $appointment->servicePackageLabel() }}
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Thời Gian Hẹn
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
            </td>
        </tr>
        @if($car)
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                    Dòng Xe
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                    {{ $car->name }}
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Địa Điểm Showroom
            </td>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                <span style="color: #F8FAFC; text-decoration: none;">BMW Showroom Flagship — 123 Nguyễn Văn Linh, Quận 7, TP.HCM</span>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 24px 0 18px 0;">
        <tr>
            <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <a href="{{ $appointment->temporaryManageUrl() }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px;">
                    XÁC NHẬN / QUẢN LÝ LỊCH HẸN
                </a>
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #8E9BAE; line-height: 1.6;">
        Tệp lịch chuẩn <strong style="color: #F8FAFC;">appointment.ics</strong> được đính kèm để Quý khách đồng bộ vào Lịch điện thoại. Nếu cần đổi sang khung giờ khác, Quý khách chỉ cần nhấn vào nút quản lý lịch hẹn phía trên.
    </p>
@endsection
