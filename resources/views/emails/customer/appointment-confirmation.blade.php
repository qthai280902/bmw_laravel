@extends('emails.layouts.bmw-concierge')

@php
    $officialMode = (bool) ($isAdminConfirmed ?? (($mode ?? 'system_received') === 'admin_confirmed'));
    if (! isset($advisorFormatted) || ! isset($advisorName)) {
        if (method_exists(\App\Mail\Customer\AppointmentConfirmationMail::class, 'resolveAdvisorDisplay')) {
            $advisorDisplay = \App\Mail\Customer\AppointmentConfirmationMail::resolveAdvisorDisplay(
                $appointment,
                $confirmedByName ?? null,
                $confirmedByRole ?? null,
            );
            $advisorName = $advisorDisplay['name'];
            $advisorRole = $advisorDisplay['role'];
            $advisorFormatted = $advisorDisplay['formatted'];
        } else {
            $advisorName = filled($confirmedByName ?? null) ? trim((string) $confirmedByName) : 'Đội ngũ Chuyên viên BMW Concierge';
            $advisorRole = filled($confirmedByRole ?? null) ? trim((string) $confirmedByRole) : 'BMW Showroom Vietnam';
            $advisorFormatted = ($advisorRole !== '' && mb_stripos($advisorName, $advisorRole, 0, 'UTF-8') === false)
                ? "{$advisorName} ({$advisorRole})"
                : $advisorName;
        }
    }
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

@section('title', $officialMode ? 'Xác Nhận Chính Thức Lịch Hẹn | BMW Concierge' : 'Đã Tiếp Nhận Yêu Cầu Đặt Lịch | BMW Showroom Vietnam')
@section('accent_color', '#1C69D4')
@section('header_subtitle', $officialMode ? 'VIP Concierge Pass · Thẻ đón tiếp' : 'ĐÃ TIẾP NHẬN YÊU CẦU ĐẶT LỊCH')
@if(! $officialMode)
    @section('hide_reply_hint', '1')
@endif

@section('badge')
    @if($officialMode)
        <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
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
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, chuyên viên <strong style="color: #1C69D4;">{{ $advisorName }}</strong> đã xác nhận giữ suất <strong style="color: #0F172A;">{{ $appointment->typeLabel() }}</strong> và chuẩn bị đón tiếp Quý khách tại Showroom.
        </p>

        {{-- VIP Concierge Pass (BMW Navy Luxury Boarding Pass Style) --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0B1220; border: 1px solid #1E293B; border-radius: 12px; margin-bottom: 22px; overflow: hidden;">
            <tr>
                <td style="height: 3px; background-color: #1C69D4; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 18px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <div style="font-size: 11px; font-weight: 700; color: #70A7FF; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 10px;">
                        VIP CONCIERGE PASS &bull; ĐÃ XÁC NHẬN CHÍNH THỨC
                    </div>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding: 4px 0; font-size: 13px; color: #94A3B8; width: 38%;">Mã tham chiếu</td>
                            <td style="padding: 4px 0; font-size: 14px; font-weight: 700; color: #F8FAFC; width: 62%;">{{ $customerOrderCode }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; font-size: 13px; color: #94A3B8;">Chuyên viên phụ trách</td>
                            <td style="padding: 4px 0; font-size: 13px; font-weight: 600; color: #F8FAFC;">{{ $advisorFormatted }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; font-size: 13px; color: #94A3B8;">Đặc quyền đón tiếp</td>
                            <td style="padding: 4px 0; font-size: 13px; color: #CBD5E1;">Khu đỗ xe VIP ưu tiên &amp; phòng tiếp khách BMW Lounge</td>
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
        <div style="margin: 0 0 22px 0; border: 1px solid #1E293B; border-radius: 12px; overflow: hidden; background-color: #0B1220;">
            <img src="{{ $inlineSrc }}" alt="{{ $car ? $car->name : 'BMW Showroom Vietnam' }}" style="display: block; width: 100%; max-height: 220px; object-fit: cover; border: 0;" />
        </div>
    @else
        {{-- BMW Dark Luxury Hero Card (Service / Aftersales & Consultation without Vehicle Photo — Gmail Light & Dark Safe) --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" data-bmw-dark-luxury-hero="1" style="margin: 0 0 22px 0; background-color: #0B1220; border: 1px solid #1E293B; border-radius: 14px; overflow: hidden;">
            <tr>
                <td style="height: 4px; background-color: #1C69D4; font-size: 0; line-height: 0;">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding: 22px 24px; background-color: #0B1220; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td align="left" valign="middle">
                                <span style="display: inline-block; padding: 4px 10px; background-color: #131D31; border: 1px solid #2A3B59; border-radius: 6px; font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #70A7FF;">
                                    @if($emblemDotSrc)
                                        <img src="{{ $emblemDotSrc }}" width="6" height="6" alt="" style="display: inline-block; width: 6px; height: 6px; border-radius: 2px; vertical-align: middle; margin-right: 5px; border: 0;" />
                                    @endif
                                    {{ $isServiceAppointment ? 'BMW SERVICE EXCELLENCE' : 'BMW SHOWROOM CONCIERGE' }}
                                </span>
                            </td>
                            <td align="right" valign="middle" style="font-size: 12px; font-weight: 700; color: #94A3B8; letter-spacing: 0.5px;">
                                {{ $customerOrderCode }}
                            </td>
                        </tr>
                    </table>

                    <div style="margin: 14px 0 6px 0; font-size: 19px; font-weight: 700; color: #F8FAFC; line-height: 1.35;">
                        {{ $servicePackageName ?: $appointment->typeLabel() }}
                    </div>
                    <div style="margin: 0 0 16px 0; font-size: 13px; color: #CBD5E1; line-height: 1.55;">
                        @if(filled($customerCarModel))
                            Phương tiện tiếp nhận: <strong style="color: #FFFFFF;">{{ $customerCarModel }}</strong>
                        @elseif($isServiceAppointment)
                            Xưởng dịch vụ chính hãng BMW &bull; Trang thiết bị chẩn đoán tiêu chuẩn Đức
                        @else
                            Không gian trưng bày &amp; tư vấn cá nhân hóa BMW Việt Nam
                        @endif
                    </div>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top: 1px solid #1E293B; padding-top: 12px;">
                        <tr>
                            <td style="padding-top: 12px; font-size: 12px; color: #94A3B8; width: 50%;">
                                Khung giờ đón tiếp<br>
                                <strong style="display: inline-block; margin-top: 3px; font-size: 14px; color: #F8FAFC;">{{ $appointment->appointment_date?->format('H:i — d/m/Y') }}</strong>
                            </td>
                            <td style="padding-top: 12px; font-size: 12px; color: #94A3B8; width: 50%;">
                                Trạng thái khoang / lịch hẹn<br>
                                <strong style="display: inline-block; margin-top: 3px; font-size: 13px; color: #70A7FF;">
                                    {{ $officialMode ? 'Đã xác nhận giữ chỗ' : 'Đang chờ điều phối' }}
                                </strong>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    {{-- Summary Card (38% / 62%) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã tham chiếu
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #1C69D4; width: 62%;">
                {{ $customerOrderCode }}
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
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Chuyên viên phụ trách
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $advisorFormatted }}
            </td>
        </tr>
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
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.4px; border-radius: 10px;">
                    XÁC NHẬN / QUẢN LÝ LỊCH HẸN
                </a>
            </td>
        </tr>
    </table>

    @if(! $officialMode)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 22px; background-color: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 10px;">
            <tr>
                <td style="padding: 14px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; color: #475569; line-height: 1.6;">
                    <strong style="color: #0F172A;">Lưu ý (Email tự động 1 chiều — No-Reply):</strong>
                    Thư này được gửi tự động bởi hệ thống tiếp nhận BMW Showroom, hộp thư này không tiếp nhận phản hồi trực tiếp. Vui lòng <strong>không bấm Trả lời (Reply)</strong> email này; mọi thao tác xác nhận, đổi lịch hoặc hủy hẹn xin thực hiện qua nút <strong>Xác nhận / Quản lý lịch hẹn</strong> ở trên hoặc liên hệ Hotline <strong>1900 123 456</strong>.
                </td>
            </tr>
        </table>
    @endif
@endsection
