@extends('emails.layouts.bmw-concierge')

@php
    $officialMode = (bool) ($isAdminConfirmed ?? (($mode ?? 'system_received') === 'admin_confirmed'));
    $advisorName = filled($confirmedByName ?? null) ? trim((string) $confirmedByName) : 'Đội ngũ Chuyên viên BMW Concierge';
    $advisorRole = filled($confirmedByRole ?? null) ? trim((string) $confirmedByRole) : 'BMW Showroom Vietnam';
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
    $ticketCode = '#BMW-' . str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT);
    $customerOrderCode = $appointment->customer_order_code ?: $ticketCode;
    $dailySequenceCode = $appointment->daily_sequence_code;
@endphp

@section('title', $officialMode ? 'Xác Nhận Chính Thức Lịch Hẹn | BMW Concierge' : 'Đã Tiếp Nhận Yêu Cầu Đặt Lịch | BMW Showroom Vietnam')
@section('accent_color', $officialMode ? '#059669' : '#1C69D4')
@section('header_subtitle', $officialMode ? 'VIP Concierge Pass · Thẻ đón tiếp' : 'ĐÃ TIẾP NHẬN YÊU CẦU ĐẶT LỊCH')

@section('badge')
    @if($officialMode)
        <span style="display: inline-block; padding: 6px 14px; background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 9999px; color: #059669; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
            Chuyên viên xác nhận
        </span>
    @else
        <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
            Hệ thống tự động
        </span>
    @endif
@endsection

@section('content')
    @if($officialMode)
        <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
            Lịch hẹn của Quý khách đã được Chuyên viên BMW xác nhận
        </h1>
        <p style="margin: 0 0 24px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, chuyên viên <strong style="color: #059669;">{{ $advisorName }}</strong> đã xác nhận giữ suất <strong style="color: #0F172A;">{{ $appointment->typeLabel() }}</strong> và chuẩn bị đón tiếp Quý khách tại Showroom.
        </p>

        {{-- VIP Concierge Pass (Boarding Pass Style) --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; margin-bottom: 22px;">
            <tr>
                <td style="padding: 18px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <div style="font-size: 11px; font-weight: 700; color: #059669; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 10px;">
                        VIP CONCIERGE PASS &bull; ĐÃ XÁC NHẬN CHÍNH THỨC
                    </div>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding: 4px 0; font-size: 13px; color: #065F46; width: 38%;">Mã đón tiếp (Khách hàng)</td>
                            <td style="padding: 4px 0; font-size: 14px; font-weight: 700; color: #0F172A; width: 62%;">{{ $customerOrderCode }}</td>
                        </tr>
                        @if($dailySequenceCode)
                        <tr>
                            <td style="padding: 4px 0; font-size: 13px; color: #065F46; width: 38%;">Mã số trong ngày</td>
                            <td style="padding: 4px 0; font-size: 14px; font-weight: 700; color: #059669; width: 62%;">{{ $dailySequenceCode }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="padding: 4px 0; font-size: 13px; color: #065F46;">Chuyên viên phụ trách</td>
                            <td style="padding: 4px 0; font-size: 13px; font-weight: 600; color: #0F172A;">{{ $advisorName }} ({{ $advisorRole }})</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; font-size: 13px; color: #065F46;">Đặc quyền đón tiếp</td>
                            <td style="padding: 4px 0; font-size: 13px; color: #065F46;">Khu đỗ xe VIP ưu tiên &amp; phòng tiếp khách BMW Lounge</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @else
        <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
            Yêu cầu của Quý khách đã được Hệ thống ghi nhận
        </h1>
        <p style="margin: 0 0 24px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, hệ thống đã tiếp nhận yêu cầu <strong style="color: #0F172A;">{{ $appointment->typeLabel() }}</strong> của Quý khách. Đây là thư tiếp nhận tự động — Chuyên viên BMW sẽ sớm xác nhận và gửi Thẻ đón tiếp VIP tới Quý khách.
        </p>
    @endif

    @if($inlineSrc)
        <div style="margin: 0 0 22px 0; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; background-color: #F8FAFC;">
            <img src="{{ $inlineSrc }}" alt="{{ $car ? $car->name : 'BMW Showroom Vietnam' }}" style="display: block; width: 100%; max-height: 220px; object-fit: cover; border: 0;" />
        </div>
    @endif

    {{-- Summary Card (38% / 62%) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã khách hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: {{ $officialMode ? '#059669' : '#1C69D4' }}; width: 62%;">
                {{ $customerOrderCode }}
            </td>
        </tr>
        @if($dailySequenceCode)
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Mã ngày (STT)
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #0F172A;">
                {{ $dailySequenceCode }}
            </td>
        </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Mã hệ thống
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 600; color: #475569;">
                {{ $ticketCode }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Hạng mục
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $appointment->typeLabel() }}
            </td>
        </tr>
        @if($appointment->servicePackageLabel())
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                    Gói dịch vụ
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #1C69D4;">
                    {{ $appointment->servicePackageLabel() }}
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Thời gian hẹn
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
            </td>
        </tr>
        @if($car)
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                    Mẫu xe quan tâm
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                    {{ $car->name }}
                    @if($car->price)
                        <span style="display: block; font-size: 12px; font-weight: 500; color: #1C69D4; margin-top: 2px;">
                            Giá niêm yết: {{ number_format((float) $car->price, 0, ',', '.') }} VNĐ
                        </span>
                    @endif
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; font-size: 13px; font-weight: 500; color: #64748B;">
                Địa điểm
            </td>
            <td style="padding: 13px 18px; font-size: 14px; font-weight: 600; color: #0F172A;">
                BMW Showroom Flagship &mdash; 123 Nguyễn Văn Linh, Q.7, TP.HCM
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 24px 0; font-size: 13px; color: #64748B; line-height: 1.6;">
        @if($car)
            Hồ sơ kỹ thuật <strong>BMW-{{ $car->slug }}-Brochure.html</strong> và tệp lịch <strong>appointment.ics</strong> được đính kèm sẵn trong thư này để Quý khách lưu vào lịch cá nhân.
        @else
            Tệp lịch hẹn <strong>appointment.ics</strong> được đính kèm sẵn trong thư này để Quý khách thêm nhanh vào Google Calendar hoặc Apple Calendar.
        @endif
    </p>

    {{-- Single Primary CTA Button --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ $appointment->temporaryManageUrl() }}"
                   style="display: inline-block; background-color: {{ $officialMode ? '#059669' : '#1C69D4' }}; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.4px; border-radius: 10px;">
                    XÁC NHẬN / QUẢN LÝ LỊCH HẸN
                </a>
            </td>
        </tr>
    </table>
@endsection
