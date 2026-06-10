# CRM Flows

CRM leads duoc luu trong `appointments`.

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

## meta_data

- `meta_data` la cot JSON cast array.
- Dung cho du lieu linh hoat theo tung loai lead.
- Khong ep schema moi neu chua can.
- Khi doc phai xu ly null/array an toan.
