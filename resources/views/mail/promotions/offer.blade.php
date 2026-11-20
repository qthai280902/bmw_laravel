@extends('emails.layouts.bmw-concierge')

@section('title', $offerTitle . ' | BMW Showroom Vietnam')
@section('accent_color', '#1C69D4')
@section('header_subtitle', 'Đặc quyền tri ân khách hàng BMW')

@section('badge')
    <span style="display: inline-block; padding: 6px 14px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 9999px; color: #1C69D4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700;">
        Ưu đãi đặc quyền
    </span>
@endsection

@section('content')
    <h1 style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.35;">
        {{ $offerTitle }}
    </h1>
    <p style="margin: 0 0 22px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; color: #475569; line-height: 1.6;">
        Kính chào Quý khách <strong style="color: #0F172A;">{{ $customerName }}</strong>, cảm ơn Quý khách đã dành sự quan tâm đặc biệt tới dòng xe <strong style="color: #1C69D4;">{{ $vehicleName }}</strong> tại BMW Showroom Vietnam.
    </p>

    <div style="padding: 18px 20px; background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; margin-bottom: 22px;">
        <p style="margin: 0; font-size: 14px; color: #0F172A; line-height: 1.65;">
            {!! nl2br(e($offerContent)) !!}
        </p>
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 500; color: #64748B; width: 38%;">
                Khách hàng thân thiết
            </td>
            <td style="padding: 13px 18px; border-bottom: 1px solid #E2E8F0; font-size: 14px; font-weight: 600; color: #0F172A; width: 62%;">
                {{ $customerName }}
            </td>
        </tr>
        <tr>
            <td style="padding: 13px 18px; font-size: 13px; font-weight: 500; color: #64748B;">
                Dòng xe quan tâm
            </td>
            <td style="padding: 13px 18px; font-size: 14px; font-weight: 600; color: #1C69D4;">
                {{ $vehicleName }}
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="left">
                <a href="{{ $bookingUrl ?? $actionUrl ?? route('appointments.create') }}"
                   style="display: inline-block; background-color: #1C69D4; color: #FFFFFF; text-decoration: none; padding: 13px 26px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; border-radius: 10px;">
                    Đặt lịch trải nghiệm &amp; nhận ưu đãi
                </a>
            </td>
        </tr>
    </table>
@endsection
