BMW SHOWROOM VIETNAM — KHÁCH HÀNG CẬP NHẬT · THAY ĐỔI LỊCH HẸN
====================================================================

{{ $actionHeading }}

Kính chào Quý khách {{ $appointment->customer_name }},

Hệ thống BMW Showroom Vietnam đã tiếp nhận và đồng bộ thay đổi cho lịch hẹn {{ $appointment->publicReferenceCode() }} tới đội ngũ Cố vấn dịch vụ và Bán hàng.

ĐỐI CHIẾU THÔNG TIN TRƯỚC & SAU CẬP NHẬT
--------------------------------------------------------------------
- Hạng mục: {{ $appointment->typeLabel() }}
@if($appointment->product)
- Dòng xe: {{ $appointment->product->name }}
@endif
- Thời gian trước thay đổi: {{ $previousDateLabel ?: ($newDateLabel ?: 'Chưa xác định') }}
- Thời gian mới cập nhật: {{ $newDateLabel ?: 'Chưa xác định' }}
- Trạng thái trước thay đổi: {{ $previousStatusLabel ?: $newStatusLabel }}
- Trạng thái mới cập nhật: {{ $newStatusLabel }}
@if(filled($reason))
- Ghi chú từ Quý khách: {{ $reason }}
@endif

@if($action !== \App\Mail\Customer\GuestAppointmentChangeMail::ACTION_CANCEL)
Tệp lịch cập nhật appointment.ics đã được đính kèm trong email này để Quý khách đồng bộ lại vào lịch cá nhân.
@endif

Liên kết quản lý lịch hẹn (Signed URL):
{{ $appointment->temporaryManageUrl() }}

BMW Showroom Flagship — 123 Nguyễn Văn Linh, Quận 7, TP.HCM
Hotline Concierge: 1900 123 456
