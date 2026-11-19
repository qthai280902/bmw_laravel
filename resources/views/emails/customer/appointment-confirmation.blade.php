@extends('emails.layouts.bmw-concierge')

@section('title', 'Xác Nhận Lịch Hẹn | BMW Showroom Vietnam')

@section('badge')
    <span style="display: inline-block; padding: 5px 12px; background-color: #16243D; border: 1px solid #1C69D4; color: #60A5FA; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;">
        CONFIRMED BOOKING
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
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 2px; color: #60A5FA; text-transform: uppercase; margin-bottom: 8px;">
        XÁC NHẬN ĐẶT LỊCH TẠI BMW SHOWROOM
    </div>
    <h1 style="margin: 0 0 18px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 20px; font-weight: 700; color: #F8FAFC; line-height: 1.35;">
        Kính chào Quý khách {{ $appointment->customer_name }},
    </h1>

    @if($inlineSrc)
        <div style="margin: 0 0 20px 0; border: 1px solid #1E2533; background-color: #0E121B;">
            <img src="{{ $inlineSrc }}" alt="{{ $car ? $car->name : 'BMW Showroom Vietnam' }}" style="display: block; width: 100%; max-height: 220px; object-fit: cover; border: 0;" />
        </div>
    @endif

    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #CBD5E1; line-height: 1.65;">
        BMW Showroom Vietnam trân trọng xác nhận đã tiếp nhận yêu cầu đặt lịch <strong style="color: #F8FAFC; font-weight: 600;">{{ $appointment->typeLabel() }}</strong> của Quý khách. Đội ngũ Chuyên viên Tư vấn (BMW Genius) đang chuẩn bị sẵn sàng để đón tiếp Quý khách chu đáo nhất.
    </p>

    {{-- Bảng thông tin lịch hẹn --}}
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
                    Gói Dịch Vụ Chăm Sóc
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
                    Dòng Xe Quan Tâm
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                    {{ $car->name }}
                    @if($car->price)
                        <span style="display: block; font-size: 12px; font-weight: 500; color: #60A5FA; margin-top: 2px;">
                            Giá niêm yết: {{ number_format((float) $car->price, 0, ',', '.') }} VNĐ
                        </span>
                    @endif
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Địa Điểm Đón Tiếp
            </td>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                <span style="color: #F8FAFC; text-decoration: none;">BMW Showroom Flagship — 123 Nguyễn Văn Linh, Quận 7, TP.HCM</span>
            </td>
        </tr>
    </table>

    @if($car)
        <div style="padding: 16px 20px; background-color: #161F30; border-left: 3px solid #1C69D4; margin-bottom: 24px;">
            <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; color: #60A5FA; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">
                TÀI LIỆU KỸ THUẬT E-BROCHURE ĐÍNH KÈM
            </div>
            <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #CBD5E1; line-height: 1.6;">
                Chúng tôi đã đính kèm hồ sơ thông số kỹ thuật chi tiết <strong style="color: #F8FAFC;">BMW-{{ $car->slug }}-Brochure.html</strong> cùng tệp lịch hẹn <strong style="color: #F8FAFC;">appointment.ics</strong> ngay trong email này để Quý khách thuận tiện lưu vào Lịch cá nhân.
            </p>
        </div>
    @else
        <div style="padding: 16px 20px; background-color: #161F30; border-left: 3px solid #1C69D4; margin-bottom: 24px;">
            <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; color: #60A5FA; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">
                TỆP LỊCH HẸN (.ICS) ĐÍNH KÈM
            </div>
            <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #CBD5E1; line-height: 1.6;">
                Tệp lịch hẹn <strong style="color: #F8FAFC;">appointment.ics</strong> đã được đính kèm trong email này để Quý khách thêm nhanh vào Google Calendar, Apple Calendar hoặc Outlook.
            </p>
        </div>
    @endif

    {{-- Nút Signed URL Quản lý lịch hẹn --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 26px 0 18px 0;">
        <tr>
            <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <a href="{{ $appointment->temporaryManageUrl() }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px;">
                    XÁC NHẬN / QUẢN LÝ LỊCH HẸN
                </a>
                @if($car)
                    <a href="{{ route('products.show', $car->slug) }}"
                       style="display: inline-block; background-color: #1A2232; border: 1px solid #2D3748; color: #E2E8F0; text-decoration: none; padding: 12px 22px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px; margin-left: 10px;">
                        XEM CHI TIẾT {{ strtoupper($car->name) }}
                    </a>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #8E9BAE; line-height: 1.6;">
        Liên kết quản lý lịch hẹn được bảo mật bằng chữ ký số (Signed URL) dành riêng cho Quý khách để xác nhận tham dự, dời khung giờ hoặc hủy lịch mà không cần đăng nhập tài khoản.
    </p>
@endsection
