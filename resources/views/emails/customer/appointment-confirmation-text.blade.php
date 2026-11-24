@php
    $car = $car ?? $appointment->product;
    $officialMode = (bool) ($isAdminConfirmed ?? (($mode ?? 'system_received') === 'admin_confirmed'));
    if (! isset($advisorFormatted) || ! isset($advisorName)) {
        if (method_exists(\App\Mail\Customer\AppointmentConfirmationMail::class, 'resolveAdvisorDisplay')) {
            $advisorDisplay = \App\Mail\Customer\AppointmentConfirmationMail::resolveAdvisorDisplay(
                $appointment,
                $confirmedByName ?? null,
                $confirmedByRole ?? null,
            );
            $advisorName = $advisorDisplay['name'];
            $advisorRole = $advisorDisplay['role'];
            $advisorFormatted = $advisorDisplay['formatted'];
        } else {
            $advisorName = filled($confirmedByName ?? null) ? trim((string) $confirmedByName) : 'Đội ngũ Chuyên viên BMW Concierge';
            $advisorRole = filled($confirmedByRole ?? null) ? trim((string) $confirmedByRole) : 'BMW Showroom Vietnam';
            $advisorFormatted = ($advisorRole !== '' && mb_stripos($advisorName, $advisorRole, 0, 'UTF-8') === false)
                ? "{$advisorName} ({$advisorRole})"
                : $advisorName;
        }
    }
@endphp
@if($officialMode)
BMW CONCIERGE — XÁC NHẬN CHÍNH THỨC LỊCH HẸN (VIP CONCIERGE PASS)
=================================================================

Kính chào Quý khách {{ $appointment->customer_name }},

Lịch hẹn của Quý khách đã được Chuyên viên BMW xác nhận chính thức.
- Chuyên viên phụ trách: {{ $advisorFormatted }}
- Hotline đón tiếp trực tiếp: 1900 123 456
- Chuẩn bị đón tiếp tại Showroom: Khu vực đỗ xe VIP, xe mẫu và kỹ thuật viên đã được giữ suất đúng khung giờ hẹn của Quý khách.
@else
BMW SHOWROOM VIETNAM — ĐÃ TIẾP NHẬN YÊU CẦU ĐẶT LỊCH (CHỜ XÁC NHẬN)
===================================================================

Kính chào Quý khách {{ $appointment->customer_name }},

Yêu cầu của Quý khách đã được Hệ thống ghi nhận. Chuyên viên tư vấn BMW đang kiểm tra khung giờ trống và sẽ gửi thư xác nhận chính thức kèm liên hệ trong thời gian sớm nhất.
@endif

THÔNG TIN LỊCH HẸN
- Mã tham chiếu: {{ $appointment->publicReferenceCode() }}
- Hạng mục: {{ $appointment->typeLabel() }}
@if($appointment->servicePackageLabel())
- Gói dịch vụ chăm sóc: {{ $appointment->servicePackageLabel() }}
@endif
- Thời gian hẹn: {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
- Chuyên viên phụ trách: {{ $advisorFormatted }}
@if($car)
- Dòng xe quan tâm: {{ $car->name }}
@if($car->price)
- Giá niêm yết: {{ number_format((float) $car->price, 0, ',', '.') }} VNĐ
@endif
@endif
- Địa điểm đón tiếp: BMW Showroom Flagship — 123 Nguyễn Văn Linh, Quận 7, TP.HCM

QUẢN LÝ LỊCH HẸN TRỰC TUYẾN (SIGNED URL)
{{ $appointment->temporaryManageUrl() }}

@if($car)
Hồ sơ kỹ thuật BMW-{{ $car->slug }}-Brochure.html và tệp lịch hẹn (.ics) đã được đính kèm trong email này.
@else
Tệp lịch hẹn (.ics) đã được đính kèm trong email này để Quý khách thêm nhanh vào Lịch cá nhân.
@endif

@if($officialMode)
Quý khách có thể bấm Trả lời (Reply) trực tiếp email này hoặc gọi Hotline Concierge: 1900 123 456.
@else
LƯU Ý (EMAIL TỰ ĐỘNG 1 CHIỀU — NO-REPLY): Đây là thư tiếp nhận tự động từ hệ thống, vui lòng không trả lời (Reply) trực tiếp email này. Để đổi lịch hoặc hủy hẹn, vui lòng sử dụng liên kết Quản lý lịch hẹn ở trên hoặc gọi Hotline Concierge: 1900 123 456.
@endif

Trân trọng,
BMW Showroom Vietnam
