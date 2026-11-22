@php
    $purchasedMode = (bool) ($isPurchased ?? (($outcome ?? 'visited_no_purchase') === 'purchased'));
    if (! isset($advisorFormatted)) {
        if (method_exists(\App\Mail\Customer\AppointmentConfirmationMail::class, 'resolveAdvisorDisplay')) {
            $advisorDisplay = \App\Mail\Customer\AppointmentConfirmationMail::resolveAdvisorDisplay($appointment);
            $advisorFormatted = $advisorDisplay['formatted'];
        } else {
            $advisorFormatted = 'Đội ngũ Chuyên viên BMW Concierge (BMW Showroom Vietnam)';
        }
    }
    $car = $appointment->product;
@endphp
@if($purchasedMode)
BMW SHOWROOM VIETNAM — CHÚC MỪNG QUÝ KHÁCH SỞ HỮU XE BMW & ĐẶC QUYỀN BMW CARE 01 THÁNG
======================================================================================

Kính chào Quý khách {{ $appointment->customer_name }},

BMW Showroom Vietnam cùng chuyên viên {{ $advisorFormatted }} trân trọng chúc mừng và cảm ơn Quý khách đã tin chọn sở hữu {{ $car?->name ?? 'dòng xe BMW chính hãng' }}.

ĐẶC QUYỀN CHỦ XE MỚI (RỬA XE & CHĂM SÓC BỀ MẶT 01 THÁNG):
- Mã đặc quyền BMW Care: {{ $voucherCode }}
- Hiệu lực sử dụng đến hết ngày: {{ $voucherExpiresLabel }} (01 tháng)
- Quyền lợi: Rửa xe tiêu chuẩn Detailing & chăm sóc bề mặt sơn/nội thất miễn phí trong 01 tháng đầu tiên tại xưởng dịch vụ chính hãng.
@else
BMW SHOWROOM VIETNAM — THƯ CẢM ƠN QUÝ KHÁCH ĐÃ THAM QUAN & TRẢI NGHIỆM
======================================================================

Kính chào Quý khách {{ $appointment->customer_name }},

Đội ngũ BMW Showroom Vietnam cùng chuyên viên {{ $advisorFormatted }} chân thành cảm ơn Quý khách đã dành thời gian ghé thăm Showroom và trực tiếp trải nghiệm {{ $car?->name ?? 'các dòng xe BMW chính hãng' }}.

Khi Quý khách cần thêm thông tin cấu hình hoặc tư vấn phương án tài chính trong thời gian tới, chuyên viên phụ trách luôn sẵn sàng đồng hành cùng Quý khách.
@endif

THÔNG TIN HỒ SƠ ĐÓN TIẾP
- Mã tham chiếu: {{ $appointment->publicReferenceCode() }}
- Hạng mục: {{ $appointment->typeLabel() }}{{ $car ? ' — ' . $car->name : '' }}
- Chuyên viên phụ trách: {{ $advisorFormatted }}
- Hotline hỗ trợ: 1900 123 456

Trân trọng,
BMW Showroom Vietnam
