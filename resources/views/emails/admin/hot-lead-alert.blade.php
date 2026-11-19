@extends('emails.layouts.bmw-concierge')

@section('title', '[HOT LEAD AI] Khách hàng tiềm năng cao | BMW Showroom')

@section('badge')
    <span style="display: inline-block; padding: 5px 12px; background-color: #3B1219; border: 1px solid #E11D48; color: #FDA4AF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;">
        HOT LEAD AI
    </span>
@endsection

@section('content')
    @php
        $car = $car ?? $appointment->product;
    @endphp
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 2px; color: #FB7185; text-transform: uppercase; margin-bottom: 8px;">
        CẢNH BÁO KHÁCH HÀNG TIỀM NĂNG CAO (SLA PHẢN HỒI: 15 PHÚT)
    </div>
    <h1 style="margin: 0 0 18px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 20px; font-weight: 700; color: #F8FAFC; line-height: 1.35;">
        {{ $appointment->customer_name }} &mdash; {{ $car ? $car->name : 'Tư vấn chung tại Showroom' }}
    </h1>

    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #CBD5E1; line-height: 1.65;">
        Trợ lý ảo <strong style="color: #F8FAFC;">BMW AI Consultant</strong> vừa phân loại lịch hẹn này ở mức ưu tiên <strong style="color: #FDA4AF;">HOT LEAD</strong> dựa trên tín hiệu mua xe và lịch sử tương tác trực tiếp của khách hàng.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0E121B; border: 1px solid #1E2533; margin-bottom: 22px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px; width: 36%;">
                Khách Hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                {{ $appointment->customer_name }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Số Điện Thoại
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                <a href="tel:{{ $appointment->customer_phone }}" style="color: #F8FAFC; text-decoration: none;">{{ $appointment->customer_phone }}</a>
            </td>
        </tr>
        @if($appointment->customer_email)
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                    Email
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                    <span style="color: #F8FAFC; text-decoration: none;">{{ $appointment->customer_email }}</span>
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Hạng Mục &amp; Dòng Xe
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #60A5FA;">
                {{ $appointment->typeLabel() }} &mdash; {{ $car ? $car->name : 'Tư vấn chung tại Showroom' }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Thời Gian Hẹn
            </td>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
            </td>
        </tr>
    </table>

    @if($appointment->ai_lead_note)
        <div style="padding: 16px 20px; background-color: #261219; border: 1px solid #881337; border-left: 3px solid #E11D48; margin-bottom: 18px;">
            <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; color: #FDA4AF; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">
                NHẬN ĐỊNH TÍN HIỆU MUA HÀNG TỪ AI
            </div>
            <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #FFE4E6; line-height: 1.6;">
                {{ $appointment->ai_lead_note }}
            </p>
        </div>
    @endif

    @if($appointment->ai_chat_summary)
        <div style="padding: 16px 20px; background-color: #0E121B; border: 1px solid #1E2533; border-left: 3px solid #1C69D4; margin-bottom: 22px;">
            <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; color: #60A5FA; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">
                TÓM TẮT HỘI THOẠI TRƯỚC KHI ĐẶT LỊCH
            </div>
            <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #CBD5E1; line-height: 1.65;">
                {!! nl2br(e($appointment->ai_chat_summary)) !!}
            </p>
        </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
        <tr>
            <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <a href="{{ route('admin.appointments.index', ['priority' => 'hot']) }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px;">
                    MỞ BẢNG ĐIỀU KHIỂN HOT LEAD
                </a>
                @if($appointment->ai_conversation_id)
                    <a href="{{ route('admin.ai-conversations.show', $appointment->ai_conversation_id) }}"
                       style="display: inline-block; background-color: #1A2232; border: 1px solid #2D3748; color: #E2E8F0; text-decoration: none; padding: 12px 22px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px; margin-left: 10px;">
                        XEM LỊCH SỬ CHAT AI
                    </a>
                @endif
            </td>
        </tr>
    </table>
@endsection
