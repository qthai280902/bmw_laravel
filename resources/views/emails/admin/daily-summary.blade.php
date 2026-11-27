@extends('emails.layouts.bmw-concierge')

@section('title', 'Báo Cáo Tổng Kết Ngày ' . ($summary['date_label'] ?? now()->format('d/m/Y')) . ' | BMW Showroom')
@section('accent_color', '#1C69D4')
@section('header_subtitle', 'Tổng kết vận hành Showroom (18:00)')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        Báo cáo ngày
    </span>
@endsection

@section('content')
    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        Tổng hợp hoạt động ngày {{ $summary['date_label'] ?? now()->format('d/m/Y') }}
    </h1>
    <p style="margin: 0 0 24px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Báo cáo nhanh các chỉ số tiếp nhận khách hàng, lịch dịch vụ chăm sóc xe và đơn phụ kiện chính hãng trong ngày tại BMW Showroom Vietnam.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 58%;">
                Tổng lịch hẹn mới trong ngày
            </td>
            <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 15px; font-weight: 700; color: #0F172A; width: 42%;">
                {{ $summary['total_appointments'] ?? 0 }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Số HOT LEAD mới
            </td>
            <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 15px; font-weight: 700; color: #DC2626;">
                {{ $summary['hot_leads'] ?? 0 }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Lịch dịch vụ chăm sóc xe mới
            </td>
            <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 15px; font-weight: 700; color: #1C69D4;">
                {{ $summary['service_bookings'] ?? 0 }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Lịch đã xác nhận / hoàn thành
            </td>
            <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 15px; font-weight: 700; color: #1C69D4;">
                {{ $summary['confirmed_or_completed'] ?? 0 }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Đơn phụ kiện mới
            </td>
            <td align="right" style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 15px; font-weight: 700; color: #0F172A;">
                {{ $summary['accessory_orders_count'] ?? 0 }}
            </td>
        </tr>
        <tr>
            <td style="padding: 15px 18px; background-color: #EFF6FF; font-size: 13px; font-weight: 700; color: #1E40AF;">
                Tổng giá trị đơn phụ kiện
            </td>
            <td align="right" style="padding: 15px 18px; background-color: #EFF6FF; font-size: 16px; font-weight: 700; color: #1C69D4;">
                {{ number_format((float) ($summary['accessory_orders_value'] ?? 0), 0, ',', '.') }} VNĐ
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ route('dashboard') }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; border-radius: 10px;">
                    Mở bảng điều khiển Showroom
                </a>
            </td>
        </tr>
    </table>
@endsection
