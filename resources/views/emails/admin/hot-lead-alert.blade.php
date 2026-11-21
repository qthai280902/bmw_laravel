@extends('emails.layouts.bmw-concierge')

@section('title', '[CRM NỘI BỘ · HOT LEAD] Khách hàng tiềm năng cao | BMW Showroom')
@section('accent_color', '#DC2626')
@section('header_subtitle', 'Cảnh báo nội bộ · SLA phản hồi 15 phút')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 9999px; color: #DC2626; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        Nội bộ CRM
    </span>
@endsection

@section('content')
    @php
        $car = $car ?? $appointment->product;
    @endphp

    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        HOT LEAD: {{ $appointment->customer_name }}
    </h1>
    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Trợ lý AI vừa phát hiện tín hiệu chốt xe / mua hàng mức ưu tiên <strong style="color: #DC2626;">HOT LEAD</strong>. Đề nghị đội Kinh doanh liên hệ ngay trong vòng 15 phút.
    </p>

    {{-- Unified Internal Alert Card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã hồ sơ
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #1C69D4; width: 62%;">
                {{ $appointment->publicReferenceCode() }}
                <span style="color: #64748B; font-weight: 500;"> &middot; </span>
                <span style="color: #0F172A;">{{ $appointment->internalVoucherCode() }}</span>
                <span style="color: #64748B; font-weight: 500;"> &middot; </span>
                <span style="color: #475569;">{{ $appointment->internalCustomerCode() }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Khách hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #0F172A; width: 62%;">
                {{ $appointment->customer_name }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Số điện thoại
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 15px; font-weight: 700; color: #DC2626;">
                <a href="tel:{{ $appointment->customer_phone }}" style="color: #DC2626; text-decoration: none;">{{ $appointment->customer_phone }}</a>
            </td>
        </tr>
        @if($appointment->customer_email)
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                    Email
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                    {{ $appointment->customer_email }}
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Nhu cầu
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #1C69D4;">
                {{ $appointment->typeLabel() }} &mdash; {{ $car ? $car->name : 'Tư vấn chung tại Showroom' }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; {{ ($appointment->ai_lead_note || $appointment->ai_chat_summary) ? 'border-bottom: 1px solid #E2E8F0;' : '' }} font-size: 13px; font-weight: 500; color: #64748B;">
                Thời gian hẹn
            </td>
            <td style="padding: 13px 18px; {{ ($appointment->ai_lead_note || $appointment->ai_chat_summary) ? 'border-bottom: 1px solid #E2E8F0;' : '' }} font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
            </td>
        </tr>
        @if($appointment->ai_lead_note)
            <tr>
                <td style="padding: 13px 18px; {{ $appointment->ai_chat_summary ? 'border-bottom: 1px solid #E2E8F0;' : '' }} background-color: #FEF2F2; font-size: 13px; font-weight: 600; color: #B91C1C;" valign="top">
                    Nhận định AI
                </td>
                <td style="padding: 13px 18px; {{ $appointment->ai_chat_summary ? 'border-bottom: 1px solid #E2E8F0;' : '' }} background-color: #FEF2F2; font-size: 13px; color: #7F1D1D; line-height: 1.55;">
                    {{ $appointment->ai_lead_note }}
                </td>
            </tr>
        @endif
        @if($appointment->ai_chat_summary)
            <tr>
                <td style="padding: 13px 18px; font-size: 13px; font-weight: 500; color: #64748B;" valign="top">
                    Tóm tắt hội thoại
                </td>
                <td style="padding: 13px 18px; font-size: 13px; color: #334155; line-height: 1.6;">
                    {!! nl2br(e($appointment->ai_chat_summary)) !!}
                </td>
            </tr>
        @endif
    </table>

    {{-- 1 Primary CTA + Optional Secondary Text Link --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ route('admin.appointments.index', ['priority' => 'hot']) }}"
                   style="display: inline-block; background-color: #DC2626; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; border-radius: 10px;">
                    Mở bảng điều khiển HOT LEAD
                </a>
                @if($appointment->ai_conversation_id)
                    <div style="margin-top: 12px;">
                        <a href="{{ route('admin.ai-conversations.show', $appointment->ai_conversation_id) }}"
                           style="font-size: 13px; font-weight: 600; color: #1C69D4; text-decoration: underline;">
                            Xem chi tiết lịch sử chat AI &rarr;
                        </a>
                    </div>
                @endif
            </td>
        </tr>
    </table>
@endsection
