@php
    $car = $car ?? $appointment->product;
@endphp
BMW SHOWROOM VIETNAM — NHẮC LỊCH HẸN TRƯỚC 24 GIỜ
=================================================

Kính chào Quý khách {{ $appointment->customer_name }},

BMW Showroom Vietnam xin trân trọng nhắc lịch hẹn {{ $appointment->typeLabel() }} của Quý khách sẽ diễn ra trong vòng 24 giờ tới.

CHI TIẾT LỊCH HẸN
- Mã lịch hẹn: #BMW-{{ str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT) }}
- Hạng mục: {{ $appointment->typeLabel() }}
@if($appointment->servicePackageLabel())
- Gói dịch vụ: {{ $appointment->servicePackageLabel() }}
@endif
- Thời gian hẹn: {{ $appointment->appointment_date?->format('H:i — d/m/Y') }}
@if($car)
- Dòng xe: {{ $car->name }}
@endif
- Địa điểm Showroom: BMW Showroom Flagship — 123 Nguyễn Văn Linh, Quận 7, TP.HCM

XÁC NHẬN / QUẢN LÝ LỊCH HẸN:
{{ $appointment->temporaryManageUrl() }}

Nếu Quý khách cần dời khung giờ hoặc hỗ trợ thêm, vui lòng bấm Trả lời (Reply) email này hoặc gọi Hotline: 1900 123 456.

Trân trọng,
BMW Showroom Vietnam
