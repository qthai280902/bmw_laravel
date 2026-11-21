@extends('emails.layouts.bmw-concierge')

@php
    $refCode = $order->publicReferenceCode();
@endphp

@section('title', 'Đơn Phụ Kiện Mới ' . $refCode . ' | BMW Showroom')
@section('accent_color', '#DC2626')
@section('header_subtitle', 'Đơn đặt hàng phụ kiện mới · Nội bộ CRM')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 9999px; color: #DC2626; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        Nội bộ CRM
    </span>
@endsection

@section('content')
    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        Đơn phụ kiện mới {{ $refCode }}
    </h1>
    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Khách hàng <strong style="color: #0F172A;">{{ $order->customer_name }}</strong> vừa đặt mua phụ kiện chính hãng BMW trên website.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã tham chiếu
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #1C69D4; width: 62%;">
                {{ $refCode }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Chứng từ &amp; Mã KH nội bộ
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #0F172A;">
                {{ $order->internalVoucherCode() }} &middot; <span style="color: #475569;">{{ $order->internalCustomerCode() }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Phụ kiện đặt mua
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $order->product?->name ?? 'Phụ kiện chính hãng BMW' }} <span style="color: #64748B; font-weight: 500;">(x{{ $order->quantity }})</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Khách hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $order->customer_name }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Số điện thoại
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 15px; font-weight: 700; color: #DC2626;">
                <a href="tel:{{ $order->customer_phone }}" style="color: #DC2626; text-decoration: none;">{{ $order->customer_phone }}</a>
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Địa chỉ giao hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 500; color: #0F172A;">
                {{ $order->customer_address }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; font-size: 13px; font-weight: 500; color: #64748B;">
                Tổng thanh toán
            </td>
            <td style="padding: 13px 18px; font-size: 15px; font-weight: 700; color: #1C69D4;">
                {{ number_format((float) $order->total_amount, 0, ',', '.') }} VNĐ
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ route('admin.accessory-orders.show', $order) }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; border-radius: 10px;">
                    Xem chi tiết đơn {{ $refCode }}
                </a>
            </td>
        </tr>
    </table>
@endsection
