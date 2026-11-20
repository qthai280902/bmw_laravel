@extends('emails.layouts.bmw-concierge')

@section('title', '[CRM NỘI BỘ · LỊCH MỚI] Lịch Hẹn Mới Tại Showroom | BMW Showroom')
@section('accent_color', '#DC2626')
@section('header_subtitle', 'Thông báo lịch hẹn mới · Dành cho nhân sự')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 9999px; color: #DC2626; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        Nội bộ CRM
    </span>
@endsection

@section('content')
    @php
        $car = $car ?? $appointment->product;
        $ticketCode = '#BMW-' . str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT);
        $customerOrderCode = $appointment->customer_order_code ?: $ticketCode;
        $dailySequenceCode = $appointment->daily_sequence_code;
    @endphp

    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        Lịch hẹn mới: {{ $appointment->customer_name }}
    </h1>
    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Hệ thống vừa tiếp nhận lịch <strong style="color: #0F172A;">{{ $appointment->typeLabel() }}</strong> mới từ khách hàng. Vui lòng kiểm tra và liên hệ xác nhận sớm.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Mã khách hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #1C69D4; width: 62%;">
                {{ $customerOrderCode }}
            </td>
        </tr>
        @if($dailySequenceCode)
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Mã ngày (STT)
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #0F172A;">
                {{ $dailySequenceCode }}
            </td>
        </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Mã lịch hẹn
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 600; color: #475569;">
                {{ $ticketCode }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Khách hàng
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
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
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $appointment->typeLabel() }}
                @if($appointment->servicePackageLabel())
                    &mdash; <span style="color: #1C69D4;">{{ $appointment->servicePackageLabel() }}</span>
                @elseif($car)
                    &mdash; <span style="color: #1C69D4;">{{ $car->name }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; font-size: 13px; font-weight: 500; color: #64748B;">
                Thời gian hẹn
            </td>
            <td style="padding: 13px 18px; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ route('admin.appointments.index') }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; border-radius: 10px;">
                    Xử lý lịch hẹn trên Admin
                </a>
            </td>
        </tr>
    </table>
@endsection
