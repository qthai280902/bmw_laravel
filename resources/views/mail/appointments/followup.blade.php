@extends('emails.layouts.bmw-concierge')

@section('title', 'Cảm Ơn & Dự Toán Tài Chính | BMW Showroom Vietnam')
@section('accent_color', '#B45309')
@section('header_subtitle', 'Chăm sóc hậu mãi & Dự toán tài chính')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #FEF3C7; border: 1px solid #FDE68A; border-radius: 9999px; color: #B45309; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        Chăm sóc hậu mãi
    </span>
@endsection

@section('content')
    @php
        $car = $car ?? $appointment->product;
        $estimate = $estimate ?? $appointment->financialEstimate();
    @endphp

    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        Cảm ơn Quý khách đã đồng hành cùng BMW Showroom
    </h1>
    <p style="margin: 0 0 24px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, chân thành cảm ơn Quý khách đã hoàn thành buổi <strong style="color: #0F172A;">{{ $appointment->typeLabel() }}</strong>@if($car) cùng mẫu xe <strong style="color: #1C69D4;">{{ $car->name }}</strong>@endif. Chúng tôi hy vọng Quý khách đã có trải nghiệm hài lòng nhất.
    </p>

    @if($appointment->customer_order_code || $appointment->daily_sequence_code)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 20px;">
            @if($appointment->customer_order_code)
            <tr>
                <td style="padding: 11px 18px; {{ $appointment->daily_sequence_code ? 'border-bottom: 1px solid #E2E8F0;' : '' }} font-size: 13px; font-weight: 500; color: #64748B; width: 46%;">
                    Mã khách hàng
                </td>
                <td align="right" style="padding: 11px 18px; {{ $appointment->daily_sequence_code ? 'border-bottom: 1px solid #E2E8F0;' : '' }} font-size: 14px; font-weight: 700; color: #B45309; width: 54%;">
                    {{ $appointment->customer_order_code }}
                </td>
            </tr>
            @endif
            @if($appointment->daily_sequence_code)
            <tr>
                <td style="padding: 11px 18px; font-size: 13px; font-weight: 500; color: #64748B; width: 46%;">
                    Mã ngày (STT)
                </td>
                <td align="right" style="padding: 11px 18px; font-size: 14px; font-weight: 700; color: #0F172A; width: 54%;">
                    {{ $appointment->daily_sequence_code }}
                </td>
            </tr>
            @endif
        </table>
    @endif

    @if($car && is_array($estimate))
        <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px;">
            BẢNG ƯỚC TÍNH TÀI CHÍNH / TRẢ GÓP THAM KHẢO &mdash; {{ strtoupper($car->name) }}
        </div>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 46%;">
                    Giá niêm yết
                </td>
                <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A; width: 54%;">
                    {{ number_format((float) $estimate['price'], 0, ',', '.') }} VNĐ
                </td>
            </tr>
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                    Trả trước ({{ $estimate['down_payment_percent'] }}%)
                </td>
                <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                    {{ number_format((float) $estimate['down_payment'], 0, ',', '.') }} VNĐ
                </td>
            </tr>
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                    Khoản vay dự kiến ({{ $estimate['loan_percent'] }}%)
                </td>
                <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                    {{ number_format((float) $estimate['loan_amount'], 0, ',', '.') }} VNĐ
                </td>
            </tr>
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                    Kỳ hạn &amp; Lãi suất tham khảo
                </td>
                <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                    {{ $estimate['term_months'] }} tháng &bull; {{ number_format((float) $estimate['annual_interest_rate'], 1, '.', '') }}%/năm
                </td>
            </tr>
            <tr>
                <td style="padding: 15px 18px; background-color: #FEF3C7; font-size: 13px; font-weight: 700; color: #92400E;">
                    Ước tính góp mỗi tháng
                </td>
                <td align="right" style="padding: 15px 18px; background-color: #FEF3C7; font-size: 16px; font-weight: 700; color: #B45309;">
                    {{ number_format((float) $estimate['monthly_payment'], 0, ',', '.') }} VNĐ/tháng
                </td>
            </tr>
        </table>
    @endif

    <p style="margin: 0 0 24px 0; font-size: 13px; color: #64748B; line-height: 1.6;">
        Nếu Quý khách cần báo giá lăn bánh chi tiết theo khu vực hoặc cấu hình cá nhân hóa, Quý khách chỉ cần bấm <strong>Trả lời (Reply)</strong> trực tiếp email này.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ $car ? route('products.show', $car->slug) : route('appointments.create') }}"
                   style="display: inline-block; background-color: #B45309; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; border-radius: 10px;">
                    {{ $car ? 'Xem lại ' . $car->name : 'Đặt lịch tư vấn chuyên sâu' }}
                </a>
            </td>
        </tr>
    </table>
@endsection
