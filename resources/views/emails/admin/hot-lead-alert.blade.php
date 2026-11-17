@extends('emails.layouts.bmw-transactional')

@section('title', '[HOT LEAD] Yêu cầu #' . $appointment->id)

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

    <div style="margin-bottom: 12px;">
        <span style="display: inline-block; background-color: #e11d48; color: #ffffff; padding: 5px 12px; font-size: 10px; font-weight: 900; letter-spacing: 0.25em; text-transform: uppercase; border-radius: 0px;">
            HOT LEAD
        </span>
    </div>

    <h1 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; color: #ffffff;">
        Khách sẵn sàng giao dịch — #{{ $appointment->id }}
    </h1>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #09090b; border: 1px solid #27272a; border-radius: 0px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; width: 38%; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Khách hàng
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $customerName }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Hotline liên hệ
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #70A7FF; font-weight: 800;">
                {{ $customerPhone }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Email
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #d4d4d8;">
                {{ $customerEmail }}
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Dòng xe / Loại hẹn
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $productName }} ({{ $typeLabel }})
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                Thời gian mong muốn
            </td>
            <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700;">
                {{ $formattedDate }}
            </td>
        </tr>
        @if(filled($appointment->ai_lead_note))
            <tr>
                <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                    Đánh giá AI Sales
                </td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #fda4af;">
                    {{ $appointment->ai_lead_note }}
                </td>
            </tr>
        @endif
        @if(filled($appointment->ai_chat_summary))
            <tr>
                <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                    Tóm tắt Chat AI
                </td>
                <td style="padding: 14px 16px; border-bottom: 1px solid #27272a; color: #70A7FF;">
                    {{ $appointment->ai_chat_summary }}
                </td>
            </tr>
        @endif
        @if(filled($appointment->notes))
            <tr>
                <td style="padding: 14px 16px; color: #a1a1aa; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em;">
                    Ghi chú khách hàng
                </td>
                <td style="padding: 14px 16px; color: #d4d4d8;">
                    {{ $appointment->notes }}
                </td>
            </tr>
        @endif
    </table>

    <div style="text-align: left;">
        <a href="{{ route('admin.appointments.index', ['search' => $appointment->guest_phone]) }}" style="display: inline-block; background-color: #1C69D4; color: #ffffff; text-decoration: none; padding: 14px 28px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.25em; border-radius: 0px;">
            XỬ LÝ HOT LEAD NGAY
        </a>
    </div>
@endsection
