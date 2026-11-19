@php
    $car = $car ?? $appointment->product;
@endphp
BMW SHOWROOM VIETNAM — CONCIERGE & CLIENT RELATIONS
===================================================

Kính chào Quý khách {{ $appointment->customer_name }},

BMW Showroom Vietnam trân trọng xác nhận đã tiếp nhận yêu cầu đặt lịch {{ $appointment->typeLabel() }} của Quý khách.

THÔNG TIN LỊCH HẸN
- Mã lịch hẹn: #BMW-{{ str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT) }}
- Hạng mục: {{ $appointment->typeLabel() }}
@if($appointment->servicePackageLabel())
- Gói dịch vụ chăm sóc: {{ $appointment->servicePackageLabel() }}
@endif
- Thời gian hẹn: {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
@if($car)
- Dòng xe quan tâm: {{ $car->name }}
@if($car->price)
- Giá niêm yết: {{ number_format((float) $car->price, 0, ',', '.') }} VNĐ
@endif
@endif
- Địa điểm đón tiếp: BMW Showroom Flagship — 123 Nguyễn Văn Linh, Quận 7, TP.HCM

QUẢN LÝ LỊCH HẸN TRỰC TUYẾN (SIGNED URL)
Xác nhận tham dự, dời lịch hoặc hủy lịch hẹn tại liên kết sau:
{{ $appointment->temporaryManageUrl() }}

@if($car)
Hồ sơ kỹ thuật BMW-{{ $car->slug }}-Brochure.html và tệp lịch hẹn (.ics) đã được đính kèm trong email này.
@else
Tệp lịch hẹn (.ics) đã được đính kèm trong email này để Quý khách thêm nhanh vào Lịch cá nhân.
@endif

Để trao đổi trực tiếp với Chuyên viên Tư vấn, Quý khách có thể bấm Trả lời (Reply) trực tiếp email này hoặc gọi Hotline Concierge: 1900 123 456.

Trân trọng,
BMW Showroom Vietnam
