# CRM Flows

CRM leads duoc luu trong `appointments`.

Accessory orders khong con luu trong `appointments` sau Phase 13. Luong nay dung bang rieng `accessory_orders`.

Article CMS Phase 14 khong tao CRM lead truc tiep. Public "Tim hieu them" la content/education flow va van lien ket ve cac CTA hien co neu can.

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

## Phase 14 content flow

- Articles duoc quan ly rieng trong `articles`.
- Public article pages chi doc bai da published.
- Content flow khong thay doi appointment/accessory order state machine.
- Dashboard/admin CRM flow Phase 10/13 duoc giu.

## meta_data

- `meta_data` la cot JSON cast array.
- Dung cho du lieu linh hoat theo tung loai lead.
- Khong ep schema moi neu chua can.
- Khi doc phai xu ly null/array an toan.
