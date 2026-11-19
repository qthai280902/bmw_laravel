@extends('emails.layouts.bmw-concierge')

@section('title', 'Xác Nhận Thay Đổi Lịch Hẹn | BMW Showroom Vietnam')
@section('accent_color', '#F59E0B')
@section('header_subtitle', 'SELF-SERVICE APPOINTMENT UPDATE')
@section('header_subtitle_color', '#FBBF24')

@section('badge')
    <span style="display: inline-block; padding: 5px 12px; background-color: #2B1F0B; border: 1px solid #F59E0B; color: #FCD34D; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase;">
        KHÁCH HÀNG CẬP NHẬT &middot; THAY ĐỔI LỊCH HẸN
    </span>
@endsection

@section('content')
    @php
        $car = $appointment->product;
    @endphp

    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 2px; color: #FBBF24; text-transform: uppercase; margin-bottom: 8px;">
        XÁC NHẬN THAY ĐỔI TỪ QUÝ KHÁCH QUA SIGNED URL
    </div>
    <h1 style="margin: 0 0 14px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 20px; font-weight: 700; color: #F8FAFC; line-height: 1.35;">
        {{ $actionHeading }}
    </h1>
    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; color: #CBD5E1; line-height: 1.65;">
        Kính chào Quý khách <strong style="color: #F8FAFC; font-weight: 600;">{{ $appointment->customer_name }}</strong>, hệ thống BMW Showroom Vietnam đã tiếp nhận và đồng bộ thay đổi lịch hẹn <strong style="color: #FCD34D; font-weight: 600;">#BMW-{{ str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT) }}</strong> tới đội ngũ Cố vấn dịch vụ và Bán hàng.
    </p>

    {{-- Bảng đối chiếu Trước thay đổi -> Sau cập nhật --}}
    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 1.6px; color: #FBBF24; text-transform: uppercase; margin-bottom: 10px;">
        BẢNG ĐỐI CHIẾU THÔNG TIN TRƯỚC &amp; SAU CẬP NHẬT
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0E121B; border: 1px solid #3F3116; border-left: 4px solid #F59E0B; margin-bottom: 24px;">
        <tr>
            <td style="padding: 12px 16px; border-bottom: 1px solid #1E2533; background-color: #171B26; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 1.2px; width: 30%;">
                Hạng Mục
            </td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #1E2533; background-color: #171B26; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 1.2px; width: 35%;">
                Thông Tin Trước Thay Đổi
            </td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #1E2533; background-color: #171B26; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; color: #FCD34D; text-transform: uppercase; letter-spacing: 1.2px; width: 35%;">
                Thông Tin Mới Cập Nhật
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 16px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 600; color: #CBD5E1;">
                Thời gian hẹn
            </td>
            <td style="padding: 13px 16px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #94A3B8; {{ $previousDateLabel && $previousDateLabel !== $newDateLabel ? 'text-decoration: line-through;' : '' }}">
                {{ $previousDateLabel ?: ($newDateLabel ?: 'Chưa xác định') }}
            </td>
            <td style="padding: 13px 16px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #FCD34D;">
                {{ $newDateLabel ?: 'Chưa xác định' }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 16px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 600; color: #CBD5E1;">
                Trạng thái lịch
            </td>
            <td style="padding: 13px 16px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #94A3B8;">
                {{ $previousStatusLabel ?: $newStatusLabel }}
            </td>
            <td style="padding: 13px 16px; border-bottom: 1px solid #1E2533; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; font-weight: 700; color: #F8FAFC;">
                {{ $newStatusLabel }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 600; color: #CBD5E1;">
                Dịch vụ / Dòng xe
            </td>
            <td colspan="2" style="padding: 13px 16px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; font-weight: 600; color: #F8FAFC;">
                {{ $appointment->typeLabel() }}
                @if($car)
                    &mdash; <span style="color: #60A5FA;">{{ $car->name }}</span>
                @elseif($appointment->servicePackageLabel())
                    &mdash; <span style="color: #60A5FA;">{{ $appointment->servicePackageLabel() }}</span>
                @endif
            </td>
        </tr>
    </table>

    @if(filled($reason))
        <div style="padding: 14px 18px; background-color: #18140C; border: 1px solid #3F3116; border-left: 3px solid #F59E0B; margin-bottom: 22px;">
            <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; color: #FBBF24; text-transform: uppercase; margin-bottom: 6px;">
                GHI CHÚ TỪ QUÝ KHÁCH
            </div>
            <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #F8FAFC; line-height: 1.6;">
                {{ $reason }}
            </p>
        </div>
    @endif

    <div style="padding: 16px 20px; background-color: #161F30; border-left: 3px solid #F59E0B; margin-bottom: 24px;">
        <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 13px; color: #CBD5E1; line-height: 1.65;">
            Đội ngũ tư vấn viên BMW Showroom đã nhận được thông báo đồng bộ lịch hẹn mới của Quý khách.
            @if($action !== \App\Mail\Customer\GuestAppointmentChangeMail::ACTION_CANCEL)
                Tệp lịch cập nhật <strong style="color: #F8FAFC;">appointment.ics</strong> được đính kèm để Quý khách cập nhật nhanh vào Google Calendar, Apple Calendar hoặc Outlook.
            @else
                Nếu Quý khách có nhu cầu đặt lại khung giờ mới trong tương lai, chúng tôi luôn sẵn sàng phục vụ.
            @endif
        </p>
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 24px 0 8px 0;">
        <tr>
            <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
                <a href="{{ $appointment->temporaryManageUrl() }}"
                   style="display: inline-block; background-color: #D97706; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; border-radius: 0px;">
                    XEM &amp; QUẢN LÝ LỊCH HẸN
                </a>
            </td>
        </tr>
    </table>
@endsection
