@extends('emails.layouts.bmw-concierge')

@php
    $purchasedMode = (bool) ($isPurchased ?? (($outcome ?? 'visited_no_purchase') === 'purchased'));
    if (! isset($advisorFormatted) || ! isset($advisorName)) {
        if (method_exists(\App\Mail\Customer\AppointmentConfirmationMail::class, 'resolveAdvisorDisplay')) {
            $advisorDisplay = \App\Mail\Customer\AppointmentConfirmationMail::resolveAdvisorDisplay($appointment);
            $advisorName = $advisorDisplay['name'];
            $advisorFormatted = $advisorDisplay['formatted'];
        } else {
            $advisorName = 'Đội ngũ Chuyên viên BMW Concierge';
            $advisorFormatted = 'Đội ngũ Chuyên viên BMW Concierge (BMW Showroom Vietnam)';
        }
    }
    $car = $appointment->product;
    $customerOrderCode = $appointment->publicReferenceCode();
@endphp

@section('title', $purchasedMode ? 'Chúc Mừng Sở Hữu Xe BMW & Đặc Quyền Chăm Sóc 1 Tháng' : 'Thư Cảm Ơn Quý Khách Đã Tham Quan & Trải Nghiệm Tại BMW Showroom')
@section('accent_color', $purchasedMode ? '#059669' : '#1C69D4')
@section('header_subtitle', $purchasedMode ? 'CHÚC MỪNG CHỦ NHÂN BMW MỚI' : 'THƯ CẢM ƠN TỪ BMW CONCIERGE')

@section('badge')
    @if($purchasedMode)
        <span style="display: inline-block; padding: 6px 14px; background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 9999px; color: #059669; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
            Đã ký hợp đồng &bull; Đặc quyền chủ xe
        </span>
    @else
        <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
            Tri ân khách tham quan
        </span>
    @endif
@endsection

@section('content')
    @if($purchasedMode)
        <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
            Chúc mừng Quý khách chính thức gia nhập cộng đồng chủ nhân BMW
        </h1>
        <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, BMW Showroom Vietnam cùng chuyên viên <strong style="color: #059669;">{{ $advisorFormatted }}</strong> trân trọng cảm ơn Quý khách đã tin tưởng lựa chọn <strong style="color: #0F172A;">{{ $car?->name ?? 'dòng xe BMW chính hãng' }}</strong> trong buổi đón tiếp vừa qua.
        </p>

        {{-- Khối Quà Tặng Đặc Quyền Rửa Xe & Chăm Sóc Xe 1 Tháng --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; margin-bottom: 24px;">
            <tr>
                <td style="padding: 20px 22px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                    <div style="font-size: 11px; font-weight: 700; color: #059669; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 8px;">
                        ĐẶC QUYỀN CHỦ XE MỚI &bull; GÓI CHĂM SÓC &amp; RỬA XE MIỄN PHÍ 01 THÁNG
                    </div>
                    <p style="margin: 0 0 14px 0; font-size: 14px; color: #065F46; line-height: 1.55;">
                        Nhằm tri ân quyết định sở hữu xe của Quý khách, BMW Showroom gửi tặng đặc quyền <strong>Rửa xe tiêu chuẩn Detailing &amp; Chăm sóc bề mặt sơn/nội thất miễn phí trong 01 tháng đầu tiên</strong> tại xưởng dịch vụ chính hãng:
                    </p>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FFFFFF; border: 1px dashed #10B981; border-radius: 8px;">
                        <tr>
                            <td style="padding: 14px 16px;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase;">Mã đặc quyền BMW Care</div>
                                <div style="font-size: 20px; font-weight: 800; color: #059669; letter-spacing: 1px; margin-top: 4px;">{{ $voucherCode }}</div>
                                <div style="font-size: 12px; font-weight: 600; color: #0F172A; margin-top: 6px;">
                                    Hiệu lực sử dụng đến hết ngày: <span style="color: #059669;">{{ $voucherExpiresLabel }}</span> (01 tháng)
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @else
        <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
            Trân trọng cảm ơn Quý khách đã dành thời gian ghé thăm BMW Showroom
        </h1>
        <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
            Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, đội ngũ BMW Showroom Vietnam cùng chuyên viên <strong style="color: #1C69D4;">{{ $advisorFormatted }}</strong> chân thành cảm ơn Quý khách đã đến tham quan và trực tiếp cảm nhận <strong style="color: #0F172A;">{{ $car?->name ?? 'các dòng xe BMW chính hãng' }}</strong>.
        </p>
        <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; color: #475569; line-height: 1.6;">
            Chúng tôi hy vọng buổi đón tiếp đã mang lại những góc nhìn trọn vẹn về thiết kế, cảm giác lái và công nghệ của BMW. Khi Quý khách cần thêm bất kỳ thông tin cấu hình hay phương án tài chính nào trong tương lai, chuyên viên phụ trách luôn sẵn sàng hỗ trợ theo lịch trình thuận tiện nhất của Quý khách.
        </p>
    @endif

    {{-- Thông tin hồ sơ đón tiếp --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã tham chiếu
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: {{ $purchasedMode ? '#059669' : '#1C69D4' }}; width: 62%;">
                {{ $customerOrderCode }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Hạng mục đón tiếp
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $appointment->typeLabel() }}
                @if($car)
                    &mdash; {{ $car->name }}
                @endif
            </td>
        </tr>
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
                Địa điểm Showroom
            </td>
            <td style="padding: 13px 18px; font-size: 14px; font-weight: 600; color: #0F172A;">
                BMW Showroom Flagship &mdash; 123 Nguyễn Văn Linh, Q.7, TP.HCM
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ route('products.index') }}"
                   style="display: inline-block; background-color: {{ $purchasedMode ? '#059669' : '#1C69D4' }}; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.4px; border-radius: 10px;">
                    KHÁM PHÁ SHOWROOM TRỰC TUYẾN
                </a>
            </td>
        </tr>
    </table>
@endsection
