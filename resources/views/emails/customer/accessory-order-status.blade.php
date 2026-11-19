@extends('emails.layouts.bmw-concierge')

@section('title', 'Cập Nhật Đơn Hàng #ACC-' . $order->id . ' | BMW Showroom')

@section('badge')
    <span style="display: inline-block; padding: 5px 12px; background-color: #16243D; border: 1px solid #1C69D4; color: #60A5FA; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;">
        {{ strtoupper($order->status) }}
    </span>
@endsection

@section('content')
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 2px; color: #60A5FA; text-transform: uppercase; margin-bottom: 8px;">
        CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG PHỤ KIỆN
    </div>
    <h1 style="margin: 0 0 18px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 20px; font-weight: 700; color: #F8FAFC; line-height: 1.35;">
        Kính chào Quý khách {{ $order->customer_name }},
    </h1>

    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #CBD5E1; line-height: 1.65;">
        Đơn hàng phụ kiện <strong style="color: #60A5FA;">#ACC-{{ $order->id }}</strong> của Quý khách tại BMW Showroom Vietnam vừa được cập nhật sang trạng thái: <strong style="color: #F8FAFC;">{{ $order->statusLabel() }}</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0E121B; border: 1px solid #1E2533; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px; width: 38%;">
                Mã Đơn Hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #60A5FA;">
                #ACC-{{ $order->id }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Phụ Kiện Chính Hãng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                {{ $order->product?->name ?? 'Phụ kiện chính hãng BMW' }} <span style="color: #8E9BAE; font-weight: 500;">(x{{ $order->quantity }})</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Trạng Thái Hiện Tại
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 700; color: #F8FAFC;">
                {{ $order->statusLabel() }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Địa Chỉ Nhận Hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                <span style="color: #F8FAFC; text-decoration: none;">{{ $order->customer_address }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Tổng Thanh Toán
            </td>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 15px; font-weight: 700; color: #60A5FA;">
                {{ number_format((float) $order->total_amount, 0, ',', '.') }} VNĐ
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <a href="{{ route('accessories.index') }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px;">
                    TIẾP TỤC MUA SẮM TẠI BMW SHOWROOM
                </a>
            </td>
        </tr>
    </table>
@endsection
