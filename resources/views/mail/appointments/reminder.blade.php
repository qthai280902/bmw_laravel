@extends('emails.layouts.bmw-concierge')

@section('title', 'Nhắc Lịch Hẹn 24h | BMW Showroom Vietnam')
@section('accent_color', '#4F46E5')
@section('header_subtitle', 'Nhắc lịch hẹn trước 24 giờ')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 9999px; color: #4F46E5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        Nhắc lịch 24h
    </span>
@endsection

@section('content')
    @php
        $car = $car ?? $appointment->product;
        $resolvedInlinePath = $inlineImagePath ?? $car?->localImagePath();
        $resolvedFallbackPng = $fallbackBadgePng ?? \App\Mail\Customer\AppointmentConfirmationMail::fallbackBadgePngBytes();
        $inlineSrc = null;
        $emblemDotSrc = null;
        if (isset($message) && method_exists($message, 'embed') && method_exists($message, 'embedData')) {
            if ($car && is_string($resolvedInlinePath) && $resolvedInlinePath !== '' && is_file($resolvedInlinePath)) {
                $inlineSrc = $message->embed($resolvedInlinePath);
            } else {
                $emblemDotSrc = $message->embedData($resolvedFallbackPng, 'bmw-emblem-dot.png', 'image/png');
            }
        }
        $isServiceAppointment = $appointment->isServiceBooking();
        $servicePackageName = $appointment->servicePackageLabel();
        $customerCarModel = $appointment->meta_data['customer_car_model'] ?? null;
        $customerOrderCode = $appointment->publicReferenceCode();
    @endphp

    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        Còn 24 giờ nữa tới lịch hẹn tại BMW Showroom
    </h1>
    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, lịch hẹn <strong style="color: #0F172A;">{{ $appointment->typeLabel() }}</strong> của Quý khách sẽ diễn ra vào lúc <strong style="color: #4F46E5;">{{ $appointment->appointment_date?->format('H:i — d/m/Y') }}</strong>. {{ $isServiceAppointment ? 'Cố vấn Dịch vụ BMW đã sẵn sàng tiếp nhận phương tiện của Quý khách.' : 'Đội ngũ BMW Genius đã sẵn sàng đón tiếp Quý khách.' }}
    </p>

    {{-- Khối đếm ngược 24h + Checklist 2 mục --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 12px; margin-bottom: 22px;">
        <tr>
            <td style="padding: 18px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                <div style="font-size: 11px; font-weight: 700; color: #4F46E5; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px;">
                    Chuẩn bị trước khi đến {{ $isServiceAppointment ? 'Xưởng dịch vụ' : 'Showroom' }}
                </div>
                @if($isServiceAppointment)
                    <p style="margin: 0 0 6px 0; font-size: 13px; color: #312E81; line-height: 1.6;">
                        &bull; <strong>Lưu ý tiếp nhận xe:</strong> Vui lòng mang theo giấy đăng ký xe và thông báo trước với Cố vấn dịch vụ nếu có yêu cầu kiểm tra kỹ thuật phát sinh.
                    </p>
                @else
                    <p style="margin: 0 0 6px 0; font-size: 13px; color: #312E81; line-height: 1.6;">
                        &bull; <strong>Giấy tờ cần mang theo:</strong> CMND/CCCD và <strong>Bằng lái xe hạng B còn hạn</strong> (bắt buộc khi tham gia lái thử).
                    </p>
                @endif
                <p style="margin: 0; font-size: 13px; color: #312E81; line-height: 1.6;">
                    &bull; <strong>Đón tiếp ưu tiên:</strong> Vui lòng đến trước 10 phút tại 123 Nguyễn Văn Linh, Q.7, TP.HCM.
                </p>
            </td>
        </tr>
    </table>

    @if($inlineSrc)
        <div style="margin: 0 0 22px 0; border: 1px solid #1E293B; border-radius: 12px; overflow: hidden; background-color: #0B1220;">
            <img src="{{ $inlineSrc }}" alt="{{ $car ? $car->name : 'BMW Showroom Vietnam' }}" style="display: block; width: 100%; max-height: 220px; object-fit: cover; border: 0;" />
        </div>
    @else
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" data-bmw-dark-luxury-hero="1" style="margin: 0 0 22px 0; background-color: #0B1220; border: 1px solid #1E293B; border-radius: 14px; overflow: hidden;">
            <tr>
                <td style="height: 4px; background-color: #4F46E5; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 20px 22px; background-color: #0B1220; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <div style="font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #A5B4FC; margin-bottom: 8px;">
                        @if($emblemDotSrc)
                            <img src="{{ $emblemDotSrc }}" width="6" height="6" alt="" style="display: inline-block; width: 6px; height: 6px; border-radius: 2px; vertical-align: middle; margin-right: 5px; border: 0;" />
                        @endif
                        {{ $isServiceAppointment ? 'BMW SERVICE REMINDER · 24H' : 'BMW CONCIERGE REMINDER · 24H' }}
                    </div>
                    <div style="font-size: 18px; font-weight: 700; color: #F8FAFC; line-height: 1.35; margin-bottom: 6px;">
                        {{ $servicePackageName ?: $appointment->typeLabel() }}
                    </div>
                    <div style="font-size: 13px; color: #CBD5E1;">
                        @if(filled($customerCarModel))
                            Xe dịch vụ: <strong style="color: #FFFFFF;">{{ $customerCarModel }}</strong> &bull;
                        @endif
                        Mã lịch hẹn: <strong style="color: #A5B4FC;">{{ $customerOrderCode }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã tham chiếu
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #4F46E5; width: 62%;">
                {{ $customerOrderCode }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Hạng mục
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $appointment->typeLabel() }}
                @if($appointment->servicePackageLabel())
                    &mdash; <span style="color: #4F46E5;">{{ $appointment->servicePackageLabel() }}</span>
                @endif
            </td>
        </tr>
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
                    Dòng xe
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                    {{ $car->name }}
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

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ $appointment->temporaryManageUrl() }}"
                   style="display: inline-block; background-color: #4F46E5; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.4px; border-radius: 10px;">
                    XÁC NHẬN / QUẢN LÝ LỊCH HẸN
                </a>
            </td>
        </tr>
    </table>
@endsection
