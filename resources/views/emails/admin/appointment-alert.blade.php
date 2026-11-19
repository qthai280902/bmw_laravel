@extends('emails.layouts.bmw-concierge')

@section('title', 'Lịch Hẹn Mới Tại Showroom | BMW Showroom Vietnam')

@section('badge')
    <span style="display: inline-block; padding: 5px 12px; background-color: #16243D; border: 1px solid #1C69D4; color: #60A5FA; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;">
        NEW APPOINTMENT
    </span>
@endsection

@section('content')
    @php
        $car = $car ?? $appointment->product;
    @endphp
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 2px; color: #60A5FA; text-transform: uppercase; margin-bottom: 8px;">
        THÔNG BÁO LỊCH HẸN MỚI TẠI SHOWROOM
    </div>
    <h1 style="margin: 0 0 18px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 20px; font-weight: 700; color: #F8FAFC; line-height: 1.35;">
        {{ $appointment->customer_name }} &mdash; {{ $appointment->typeLabel() }}
    </h1>

    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #CBD5E1; line-height: 1.65;">
        Hệ thống vừa ghi nhận một lịch hẹn <strong style="color: #F8FAFC;">{{ $appointment->typeLabel() }}</strong> mới từ khách hàng. Vui lòng kiểm tra thông tin dưới đây và liên hệ xác nhận với khách hàng.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0E121B; border: 1px solid #1E2533; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px; width: 38%;">
                Mã Lịch Hẹn
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #60A5FA;">
                #BMW-{{ str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT) }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
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
                Hạng Mục
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                {{ $appointment->typeLabel() }}
            </td>
        </tr>
        @if($appointment->servicePackageLabel())
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                    Gói Dịch Vụ Chăm Sóc
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #60A5FA;">
                    {{ $appointment->servicePackageLabel() }}
                </td>
            </tr>
        @endif
        @if($car)
            <tr>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                    Dòng Xe Quan Tâm
                </td>
                <td style="padding: 13px 18px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #60A5FA;">
                    {{ $car->name }}
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 600; color: #8E9BAE; text-transform: uppercase; letter-spacing: 1px;">
                Thời Gian Hẹn
            </td>
            <td style="padding: 13px 18px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; font-weight: 600; color: #F8FAFC;">
                {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
        <tr>
            <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <a href="{{ route('admin.appointments.index') }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px;">
                    XỬ LÝ LỊCH HẸN TRÊN ADMIN
                </a>
            </td>
        </tr>
    </table>
@endsection
