BMW SHOWROOM VIETNAM — BÁO CÁO TỔNG KẾT VẬN HÀNH CUỐI NGÀY
==========================================================

Ngày báo cáo: {{ $summary['date_label'] ?? now()->format('d/m/Y') }}

CHỈ SỐ VẬN HÀNH TRONG NGÀY:
- Tổng lịch hẹn mới: {{ $summary['total_appointments'] ?? 0 }}
- Số HOT LEAD mới: {{ $summary['hot_leads'] ?? 0 }}
- Lịch dịch vụ chăm sóc xe mới: {{ $summary['service_bookings'] ?? 0 }}
- Lịch đã xác nhận / hoàn thành: {{ $summary['confirmed_or_completed'] ?? 0 }}
- Đơn phụ kiện mới: {{ $summary['accessory_orders_count'] ?? 0 }}
- Tổng giá trị đơn phụ kiện: {{ number_format((float) ($summary['accessory_orders_value'] ?? 0), 0, ',', '.') }} VNĐ

Mở bảng điều khiển quản trị:
{{ route('dashboard') }}

BMW Showroom Vietnam Operations
