@extends('emails.layouts.bmw-concierge')

@section('title', 'Xác Nhận Thay Đổi Lịch Hẹn | BMW Showroom Vietnam')
@section('accent_color', '#D97706')
@section('header_subtitle', 'Cập nhật lịch hẹn tự phục vụ')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #FFFBEB; border: 1px solid #FDE68A; border-radius: 9999px; color: #D97706; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        Khách hàng cập nhật
    </span>
@endsection

@section('content')
    @php
        $car = $appointment->product;
        $ticketCode = '#BMW-' . str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT);
        $customerOrderCode = $appointment->customer_order_code ?: $ticketCode;
        $dailySequenceCode = $appointment->daily_sequence_code;
    @endphp

    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        {{ $actionHeading }}
    </h1>
    <p style="margin: 0 0 24px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Kính chào Quý khách <strong style="color: #0F172A;">{{ $appointment->customer_name }}</strong>, mọi điều chỉnh cho lịch hẹn <strong style="color: #D97706;">{{ $customerOrderCode }}</strong>@if($dailySequenceCode) (Mã ngày: <strong style="color: #0F172A;">{{ $dailySequenceCode }}</strong>)@endif ({{ $ticketCode }}) đã được đồng bộ ngay tới Chuyên viên BMW phụ trách.
    </p>

    <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px;">
        BẢNG ĐỐI CHIẾU THÔNG TIN TRƯỚC &amp; SAU CẬP NHẬT
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 22px;">
        <tr>
            <td style="padding: 12px 16px; border-bottom: 1px solid #E2E8F0; font-size: 12px; font-weight: 600; color: #64748B; width: 32%;">
                Hạng mục
            </td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #E2E8F0; font-size: 12px; font-weight: 600; color: #64748B; width: 34%;">
                Trước cập nhật
            </td>
            <td style="padding: 12px 16px; border-bottom: 1px solid #E2E8F0; font-size: 12px; font-weight: 700; color: #D97706; width: 34%;">
                Mới cập nhật
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 16px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Thời gian hẹn
            </td>
            <td style="padding: 13px 16px; border-bottom: 1px solid #E2E8F0; font-size: 13px; color: #94A3B8; {{ $previousDateLabel && $previousDateLabel !== $newDateLabel ? 'text-decoration: line-through;' : '' }}">
                {{ $previousDateLabel ?: ($newDateLabel ?: 'Chưa xác định') }}
            </td>
            <td style="padding: 13px 16px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #0F172A;">
                {{ $newDateLabel ?: 'Chưa xác định' }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 16px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B;">
                Trạng thái
            </td>
            <td style="padding: 13px 16px; border-bottom: 1px solid #E2E8F0; font-size: 13px; color: #94A3B8;">
                {{ $previousStatusLabel ?: $newStatusLabel }}
            </td>
            <td style="padding: 13px 16px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 700; color: #0F172A;">
                {{ $newStatusLabel }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 16px; font-size: 13px; font-weight: 500; color: #64748B;">
                Chi tiết
            </td>
            <td colspan="2" style="padding: 13px 16px; font-size: 14px; font-weight: 600; color: #0F172A;">
                {{ $appointment->typeLabel() }}
                @if($car)
                    &mdash; <span style="color: #1C69D4;">{{ $car->name }}</span>
                @elseif($appointment->servicePackageLabel())
                    &mdash; <span style="color: #1C69D4;">{{ $appointment->servicePackageLabel() }}</span>
                @endif
            </td>
        </tr>
    </table>

    @if(filled($reason))
        <div style="padding: 14px 18px; background-color: #FFFBEB; border: 1px solid #FDE68A; border-radius: 12px; margin-bottom: 22px;">
            <div style="font-size: 11px; font-weight: 700; color: #D97706; margin-bottom: 4px;">
                Ghi chú từ Quý khách
            </div>
            <p style="margin: 0; font-size: 14px; color: #0F172A; line-height: 1.55;">
                {{ $reason }}
            </p>
        </div>
    @endif

    <p style="margin: 0 0 24px 0; font-size: 13px; color: #64748B; line-height: 1.6;">
        @if($action !== \App\Mail\Customer\GuestAppointmentChangeMail::ACTION_CANCEL)
            Tệp lịch cập nhật <strong>appointment.ics</strong> được đính kèm sẵn để Quý khách đồng bộ lên Lịch cá nhân.
        @else
            Khi có nhu cầu đặt lại khung giờ mới trong tương lai, BMW Showroom luôn sẵn sàng phục vụ Quý khách.
        @endif
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ $appointment->temporaryManageUrl() }}"
                   style="display: inline-block; background-color: #D97706; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; border-radius: 10px;">
                    Xem &amp; Quản lý lịch hẹn
                </a>
            </td>
        </tr>
    </table>
@endsection
