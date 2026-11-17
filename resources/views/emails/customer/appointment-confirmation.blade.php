@extends('emails.layouts.bmw-transactional')

@section('title', 'Xác nhận lịch hẹn #' . $appointment->id)

@section('content')
    @php
        $customerName = $appointment->user?->name ?? $appointment->guest_name ?? 'Quý khách';
        $typeLabel = $appointment->type?->label() ?? 'Lịch hẹn';
        $productName = $appointment->product?->name
            ?? ($appointment->meta_data['customer_car_model'] ?? 'Dịch vụ chung tại Showroom');
        $showroomName = $appointment->showroom
            ?? ($appointment->meta_data['showroom'] ?? 'Showroom BMW Chính hãng');
        $formattedDate = $appointment->appointment_date?->format('H:i — d/m/Y') ?? 'Đang cập nhật';
    @endphp

    <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.3em; text-transform: uppercase; color: #1C69D4; margin-bottom: 8px;">
        XÁC NHẬN YÊU CẦU #{{ $appointment->id }}
    </div>
    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; color: #ffffff;">
        {{ $typeLabel }}
    </h1>

    <p style="margin: 0 0 24px 0; color: #a1a1aa;">
        Xin chào <strong style="color: #ffffff;">{{ $customerName }}</strong>,<br>
        BMW Showroom đã ghi nhận yêu cầu <strong style="color: #ffffff;">{{ $typeLabel }}</strong> của Quý khách. Cố vấn dịch vụ sẽ liên hệ sớm nhất để xác nhận chi tiết lịch hẹn.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #09090b; border: 1px solid #27272a; border-radius: 0px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; width: 40%; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Mã lịch hẹn
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                #{{ $appointment->id }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Loại yêu cầu
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $typeLabel }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Dòng xe / Dịch vụ
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $productName }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Thời gian dự kiến
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $formattedDate }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Chi nhánh
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff;">
                {{ $showroomName }}
            </td>
        </tr>
        @if(! empty($appointment->notes))
            <tr>
                <td style="padding: 14px 16px; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                    Ghi chú
                </td>
                <td style="padding: 14px 16px; color: #a1a1aa;">
                    {{ $appointment->notes }}
                </td>
            </tr>
        @endif
    </table>

    <div style="text-align: left;">
        @if($appointment->product)
            <div style="background-color: #09090b; border: 1px solid #1C69D4; border-radius: 0px; padding: 16px; margin-bottom: 20px;">
                <div style="font-size: 10px; font-weight: 800; letter-spacing: 0.22em; text-transform: uppercase; color: #70A7FF; margin-bottom: 6px;">
                    HỒ SƠ KỸ THUẬT ĐÍNH KÈM
                </div>
                <div style="font-size: 13px; color: #e4e4e7; line-height: 1.6;">
                    Tài liệu thông số chi tiết <strong style="color: #ffffff;">BMW-{{ $appointment->product->slug }}-Brochure.html</strong>
                    ({{ number_format((int) $appointment->product->price, 0, ',', '.') }} VNĐ) đã được đính kèm cùng email này để Quý khách tiện tham khảo.
                </div>
            </div>
        @endif

        <a href="{{ route('products.index') }}" style="display: inline-block; background-color: #1C69D4; color: #ffffff; text-decoration: none; padding: 14px 28px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.25em; border-radius: 0px;">
            KHÁM PHÁ CÁC DÒNG XE BMW
        </a>
    </div>
@endsection
