@extends('emails.layouts.bmw-transactional')

@section('title', 'Xác nhận đơn đặt phụ kiện #' . $order->id)

@section('content')
    @php
        $productName = $order->product?->name ?? 'Phụ kiện chính hãng BMW';
        $unitPrice = (int) ($order->product?->price ?? 0);
        $totalAmount = $unitPrice * (int) $order->quantity;
    @endphp

    <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.3em; text-transform: uppercase; color: #1C69D4; margin-bottom: 8px;">
        ĐƠN ĐẶT PHỤ KIỆN #{{ $order->id }}
    </div>
    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; color: #ffffff;">
        Xác nhận đơn đặt hàng phụ kiện
    </h1>

    <p style="margin: 0 0 24px 0; color: #a1a1aa;">
        Xin chào <strong style="color: #ffffff;">{{ $order->customer_name }}</strong>,<br>
        Cảm ơn Quý khách đã đặt mua phụ kiện chính hãng tại BMW Showroom. Bộ phận phụ tùng & phụ kiện sẽ liên hệ qua số điện thoại <strong style="color: #ffffff;">{{ $order->customer_phone }}</strong> để xác nhận giao hàng.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #09090b; border: 1px solid #27272a; border-radius: 0px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; width: 40%; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Mã đơn hàng
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                #{{ $order->id }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Sản phẩm phụ kiện
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $productName }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Số lượng
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $order->quantity }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Tổng tiền tạm tính
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #1C69D4; font-weight: 800;">
                {{ number_format($totalAmount, 0, ',', '.') }} VNĐ
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Địa chỉ nhận hàng
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff;">
                {{ $order->customer_address }}
            </td>
        </tr>
        @if(! empty($order->notes))
            <tr>
                <td style="padding: 14px 16px; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                    Ghi chú
                </td>
                <td style="padding: 14px 16px; color: #a1a1aa;">
                    {{ $order->notes }}
                </td>
            </tr>
        @endif
    </table>

    <div style="text-align: left;">
        <a href="{{ route('accessories.index') }}" style="display: inline-block; background-color: #1C69D4; color: #ffffff; text-decoration: none; padding: 14px 28px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.25em; border-radius: 0px;">
            XEM THÊM PHỤ KIỆN CHÍNH HÃNG
        </a>
    </div>
@endsection
