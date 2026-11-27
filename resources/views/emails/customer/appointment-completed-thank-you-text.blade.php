@php
    $branch = $branchCategory ?? ($appointment->isServiceBooking() ? 'service' : ($appointment->type === \App\Enums\AppointmentType::TradeIn ? 'trade_in' : 'sales'));
    $isServiceMode = $branch === 'service';
    $isTradeInMode = $branch === 'trade_in';
    $purchasedMode = $branch === 'sales' && (bool) ($isPurchased ?? (($outcome ?? 'visited_no_purchase') === 'purchased'));
    if (! isset($advisorFormatted)) {
        if (method_exists(\App\Mail\Customer\AppointmentConfirmationMail::class, 'resolveAdvisorDisplay')) {
            $advisorDisplay = \App\Mail\Customer\AppointmentConfirmationMail::resolveAdvisorDisplay($appointment);
            $advisorFormatted = $advisorDisplay['formatted'];
        } else {
            $advisorFormatted = 'Đội ngũ Chuyên viên BMW Concierge (BMW Showroom Vietnam)';
        }
    }
    $car = $appointment->product;
    $meta = \App\Services\Crm\InteractionTimelineRecorder::normalizeMetaArray($appointment->meta_data);
    $customerCarModel = $meta['customer_car_model'] ?? null;
    $servicePackageName = $appointment->servicePackageLabel();
    $resolvedServiceNote = $serviceCompletionNote ?? ($meta['service_completion_note'] ?? null);
    $resolvedServiceCost = $serviceActualCost ?? (isset($meta['service_actual_cost']) && $meta['service_actual_cost'] !== null ? (int) $meta['service_actual_cost'] : null);
    $resolvedNextReminder = $serviceNextReminderLabel ?? ($meta['service_next_reminder_label'] ?? null);
    $resolvedTradeInNote = $tradeInNote ?? ($meta['trade_in_note'] ?? null);
@endphp
@if($isServiceMode)
XƯỞNG DỊCH VỤ BMW CHÍNH HÃNG — XÁC NHẬN HOÀN TẤT CHĂM SÓC & BÀN GIAO PHƯƠNG TIỆN
=================================================================================

Kính chào Quý khách {{ $appointment->customer_name }},

Đội ngũ Kỹ thuật viên BMW cùng Cố vấn dịch vụ {{ $advisorFormatted }} trân trọng cảm ơn Quý khách đã tin tưởng sử dụng dịch vụ chính hãng cho phương tiện {{ $customerCarModel ?: 'BMW của Quý khách' }}.

TÓM TẮT DỊCH VỤ HOÀN TẤT:
- Hạng mục: {{ $servicePackageName ?: $appointment->typeLabel() }}
- Tình trạng bàn giao: {{ ($outcome ?? '') === 'service_inspected_advised' ? 'Đã kiểm tra & tư vấn kỹ thuật thêm' : 'Đã hoàn tất dịch vụ & bàn giao xe' }}
@if(filled($resolvedServiceNote))
- Ghi chú kỹ thuật: {{ $resolvedServiceNote }}
@endif
@if($resolvedServiceCost !== null && $resolvedServiceCost > 0)
- Chi phí dịch vụ quyết toán: {{ number_format($resolvedServiceCost, 0, ',', '.') }} VNĐ
@endif
@if(filled($resolvedNextReminder))
- Khuyến nghị kỳ bảo dưỡng tiếp theo: {{ $resolvedNextReminder }}
@endif
@elseif($isTradeInMode)
BMW CERTIFIED TRADE-IN — TỔNG KẾT THẨM ĐỊNH PHƯƠNG TIỆN
=======================================================

Kính chào Quý khách {{ $appointment->customer_name }},

Chuyên viên thẩm định {{ $advisorFormatted }} chân thành cảm ơn Quý khách đã sử dụng dịch vụ định giá BMW Certified Trade-In.
@if(filled($resolvedTradeInNote))
Ghi chú thẩm định: {{ $resolvedTradeInNote }}
@endif
@elseif($purchasedMode)
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

THÔNG TIN HỒ SƠ
- Mã tham chiếu: {{ $appointment->publicReferenceCode() }}
- Hạng mục: {{ $servicePackageName ?: $appointment->typeLabel() }}{{ (! $isServiceMode && $car) ? ' — ' . $car->name : '' }}
- Phụ trách: {{ $advisorFormatted }}
- Hotline hỗ trợ: 1900 123 456

Trân trọng,
BMW Showroom Vietnam
