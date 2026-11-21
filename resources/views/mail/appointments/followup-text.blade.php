@php
    $car = $car ?? $appointment->product;
@endphp
BMW SHOWROOM VIETNAM — CẢM ƠN & HẬU MÃI SAU TRẢI NGHIỆM
=======================================================

Kính chào Quý khách {{ $appointment->customer_name }},

Cảm ơn Quý khách đã dành thời gian tới tham dự buổi {{ $appointment->typeLabel() }} tại BMW Showroom Vietnam. Chúng tôi hy vọng Quý khách đã có những trải nghiệm ấn tượng và trọn vẹn cùng đội ngũ BMW Genius.
MÃ HỒ SƠ LỊCH HẸN
- Mã tham chiếu: {{ $appointment->publicReferenceCode() }}

@if($car && is_array($estimate))
BẢNG ƯỚC TÍNH TÀI CHÍNH / TRẢ GÓP THAM KHẢO — {{ $car->name }}
- Giá niêm yết: {{ number_format((float) $estimate['price'], 0, ',', '.') }} VNĐ
- Trả trước ({{ $estimate['down_payment_percent'] }}%): {{ number_format((float) $estimate['down_payment'], 0, ',', '.') }} VNĐ
- Khoản vay dự kiến ({{ $estimate['loan_percent'] }}%): {{ number_format((float) $estimate['loan_amount'], 0, ',', '.') }} VNĐ
- Kỳ hạn vay: {{ $estimate['term_months'] }} tháng
- Lãi suất tham khảo: {{ number_format((float) $estimate['annual_interest_rate'], 1, '.', '') }}%/năm
- Số tiền góp ước tính mỗi tháng: {{ number_format((float) $estimate['monthly_payment'], 0, ',', '.') }} VNĐ/tháng

(Lưu ý: Mức trả góp trên mang tính tham khảo; Chuyên viên Tài chính BMW sẽ thiết kế phương án tối ưu nhất cho Quý khách.)
@endif

Để nhận báo giá lăn bánh chi tiết hoặc đặt lịch tư vấn chuyên sâu, Quý khách vui lòng bấm Trả lời (Reply) trực tiếp email này hoặc truy cập:
{{ route('appointments.create') }}

Hotline Concierge: 1900 123 456
BMW Showroom Vietnam
