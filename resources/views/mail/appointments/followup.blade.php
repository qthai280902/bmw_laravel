@extends('emails.layouts.bmw-concierge')

@section('title', 'Cảm Ơn & Dự Toán Tài Chính | BMW Showroom Vietnam')
@section('accent_color', '#D97706')
@section('header_subtitle', 'POST-EXPERIENCE CONCIERGE & FINANCING')
@section('header_subtitle_color', '#FBBF24')

@section('badge')
    <span style="display: inline-block; padding: 5px 12px; background-color: #2A1D0A; border: 1px solid #D97706; color: #FDE68A; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase;">
        CHĂM SÓC HẬU MÃI &middot; DỰ TOÁN TÀI CHÍNH
    </span>
@endsection

@section('content')
    @php
        $car = $car ?? $appointment->product;
        $estimate = $estimate ?? $appointment->financialEstimate();
    @endphp
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 2px; color: #FBBF24; text-transform: uppercase; margin-bottom: 8px;">
        CẢM ƠN &amp; CHĂM SÓC HẬU MÃI SAU TRẢI NGHIỆM
    </div>
    <h1 style="margin: 0 0 18px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 20px; font-weight: 700; color: #F8FAFC; line-height: 1.35;">
        Kính chào Quý khách {{ $appointment->customer_name }},
    </h1>

    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #CBD5E1; line-height: 1.65;">
        BMW Showroom Vietnam chân thành cảm ơn Quý khách đã hoàn thành buổi <strong style="color: #F8FAFC; font-weight: 600;">{{ $appointment->typeLabel() }}</strong>@if($car) cùng mẫu xe <strong style="color: #60A5FA; font-weight: 600;">{{ $car->name }}</strong>@endif. Chúng tôi hy vọng Quý khách đã có những khoảnh khắc trải nghiệm đầy cảm xúc và hài lòng với sự đồng hành của đội ngũ BMW Genius.
    </p>

    @if($car && is_array($estimate))
        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 1.6px; color: #60A5FA; text-transform: uppercase; margin-bottom: 10px;">
            BẢNG ƯỚC TÍNH TÀI CHÍNH / TRẢ GÓP THAM KHẢO &mdash; {{ strtoupper($car->name) }}
        </div>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0E121B; border: 1px solid #1E2533; margin-bottom: 24px;">
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px; width: 46%;">
                    Giá Niêm Yết
                </td>
                <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                    {{ number_format((float) $estimate['price'], 0, ',', '.') }} VNĐ
                </td>
            </tr>
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                    Trả Trước ({{ $estimate['down_payment_percent'] }}%)
                </td>
                <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                    {{ number_format((float) $estimate['down_payment'], 0, ',', '.') }} VNĐ
                </td>
            </tr>
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                    Khoản Vay Dự Kiến ({{ $estimate['loan_percent'] }}%)
                </td>
                <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                    {{ number_format((float) $estimate['loan_amount'], 0, ',', '.') }} VNĐ
                </td>
            </tr>
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                    Kỳ Hạn &amp; Lãi Suất Tham Khảo
                </td>
                <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                    {{ $estimate['term_months'] }} tháng &bull; {{ number_format((float) $estimate['annual_interest_rate'], 1, '.', '') }}%/năm
                </td>
            </tr>
            <tr>
                <td style="padding: 15px 18px; background-color: #161F30; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; color: #60A5FA; text-transform: uppercase; letter-spacing: 1px;">
                    Ước Tính Góp Mỗi Tháng
                </td>
                <td align="right" style="padding: 15px 18px; background-color: #161F30; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 16px; font-weight: 700; color: #60A5FA;">
                    {{ number_format((float) $estimate['monthly_payment'], 0, ',', '.') }} VNĐ/tháng
                </td>
            </tr>
        </table>
    @endif

    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #94A3B8; line-height: 1.65;">
        Nếu Quý khách muốn nhận bảng báo giá lăn bánh chi tiết theo khu vực, ưu đãi thanh toán hoặc cấu hình cá nhân hóa, Quý khách chỉ cần bấm <strong style="color: #F8FAFC;">Trả lời (Reply)</strong> trực tiếp email này — phản hồi sẽ được chuyển thẳng tới Chuyên viên Tư vấn BMW.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 24px 0 8px 0;">
        <tr>
            <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <a href="{{ $car ? route('products.show', $car->slug) : route('appointments.create') }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px;">
                    {{ $car ? 'XEM LẠI ' . strtoupper($car->name) : 'ĐẶT LỊCH TƯ VẤN CHUYÊN SÂU' }}
                </a>
            </td>
        </tr>
    </table>
@endsection
