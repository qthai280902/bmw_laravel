# BUG-BUILD-001 - npm run build PowerShell ExecutionPolicy

## Ngay phat hien

2026-06-06.

## Lenh gay loi

```text
npm run build
```

## Ket qua that

PowerShell chan `C:\Program Files\nodejs\npm.ps1` do ExecutionPolicy.

## Nguyen nhan

Day la loi policy cua PowerShell khi goi shim `npm.ps1`, khong phai loi Vite/Laravel app.

## Workaround

```text
npm.cmd run build
```

## Trang thai

Workaround OK. Lenh `npm.cmd run build` da pass.
