@extends('emails.layouts.bmw-concierge')

@php
    [$accent, $pillBg, $pillBorder] = match ($order->status) {
        \App\Models\AccessoryOrder::STATUS_CANCELLED => ['#DC2626', '#FEF2F2', '#FECACA'],
        default => ['#1C69D4', '#EFF6FF', '#BFDBFE'],
    };
    $refCode = $order->publicReferenceCode();
@endphp

@section('title', 'Cập Nhật Đơn Hàng ' . $refCode . ' | BMW Showroom')
@section('accent_color', $accent)
@section('header_subtitle', 'Cập nhật trạng thái đơn phụ kiện')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: {{ $pillBg }}; border: 1px solid {{ $pillBorder }}; border-radius: 9999px; color: {{ $accent }}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        {{ $order->statusLabel() }}
    </span>
@endsection

@section('content')
    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        Cập nhật đơn hàng {{ $refCode }}
    </h1>
    <p style="margin: 0 0 24px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Kính chào Quý khách <strong style="color: #0F172A;">{{ $order->customer_name }}</strong>, đơn hàng phụ kiện <strong style="color: #0F172A;">{{ $refCode }}</strong> vừa được cập nhật sang trạng thái: <strong style="color: {{ $accent }};">{{ $order->statusLabel() }}</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã tham chiếu đơn hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #1C69D4; width: 62%;">
                {{ $refCode }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Phụ kiện chính hãng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $order->product?->name ?? 'Phụ kiện chính hãng BMW' }} <span style="color: #64748B; font-weight: 500;">(x{{ $order->quantity }})</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Trạng thái hiện tại
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: {{ $accent }};">
                {{ $order->statusLabel() }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Địa chỉ nhận hàng
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
                <a href="{{ route('accessories.index') }}"
                   style="display: inline-block; background-color: {{ $accent }}; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; border-radius: 10px;">
                    Tiếp tục mua sắm tại BMW Showroom
                </a>
            </td>
        </tr>
    </table>
@endsection
