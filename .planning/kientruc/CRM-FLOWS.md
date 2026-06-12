# CRM Flows

CRM leads duoc luu trong `appointments`.

Accessory orders khong con luu trong `appointments` sau Phase 13. Luong nay dung bang rieng `accessory_orders`.

## Lead types da xac minh

Tu enum `AppointmentType`:

- `test_drive`
- `viewing`
- `maintenance`
- `detailing`
- `car_wash`
- `quote`
- `consult`
- `advisor_meeting`
- `trade_in`

## Nhom luong CRM

- Test drive: `test_drive`.
- Advisor/consultation: `advisor_meeting`, `consult`.
- Trade-in: `trade_in`.
- Service/Aftersales: `maintenance`, `detailing`, `car_wash`.

## Phase 13 product flow

- Car/motorbike:
  - test-drive/viewing/quote van di qua appointment flow.
  - compare van la vehicle-only flow.
- Accessory:
  - order di qua `accessory_orders`.
  - contact/tu van co the dung contact flow.
  - khong dung test-drive/viewing.
  - khong tham gia vehicle compare.
- `StoreAppointmentRequest` chan accessory neu type la `test_drive` hoac `viewing`.

## meta_data

- `meta_data` la cot JSON cast array.
- Dung cho du lieu linh hoat theo tung loai lead.
- Khong ep schema moi neu chua can.
- Khi doc phai xu ly null/array an toan.
