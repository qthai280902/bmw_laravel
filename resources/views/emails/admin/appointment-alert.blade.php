@extends('emails.layouts.bmw-transactional')

@section('title', 'Lead mới #' . $appointment->id)

@section('content')
    @php
        $customerName = $appointment->user?->name ?? $appointment->guest_name ?? 'Khách vãng lai';
        $customerEmail = $appointment->user?->email ?? $appointment->guest_email ?? 'Không cung cấp';
        $customerPhone = $appointment->guest_phone ?? 'Không cung cấp';
        $typeLabel = $appointment->type?->label() ?? 'Lịch hẹn';
        $productName = $appointment->product?->name
            ?? ($appointment->meta_data['customer_car_model'] ?? 'Dịch vụ chung tại Showroom');
        $formattedDate = $appointment->appointment_date?->format('H:i — d/m/Y') ?? 'Đang cập nhật';
    @endphp

    <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.3em; text-transform: uppercase; color: #1C69D4; margin-bottom: 8px;">
        THÔNG BÁO LEAD MỚI #{{ $appointment->id }}
    </div>
    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; color: #ffffff;">
        Yêu cầu {{ $typeLabel }} từ {{ $customerName }}
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #09090b; border: 1px solid #27272a; border-radius: 0px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; width: 38%; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Mã hẹn
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                #{{ $appointment->id }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Khách hàng
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $customerName }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Số điện thoại
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $customerPhone }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Email
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa;">
                {{ $customerEmail }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Loại hẹn
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
                Thời gian hẹn
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $formattedDate }}
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
        <a href="{{ route('admin.appointments.index') }}" style="display: inline-block; background-color: #1C69D4; color: #ffffff; text-decoration: none; padding: 14px 28px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.25em; border-radius: 0px;">
            MỞ QUẢN TRỊ LỊCH HẸN
        </a>
    </div>
@endsection
