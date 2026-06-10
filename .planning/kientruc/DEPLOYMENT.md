# Deployment

## Da ghi nhan tu report cu

- Render.com.
- PostgreSQL production theo report deployment.
- Docker/Apache duoc nhac trong report Phase 8.5.

## Lenh Laravel thuong dung

- `php artisan config:clear`
- `php artisan view:clear`
- `php artisan view:cache`
- `php artisan route:list`

## Frontend build

- Tren Windows PowerShell, neu `npm run build` bi ExecutionPolicy chan `npm.ps1`, dung:

```text
npm.cmd run build
```

## Chua xac minh

- Khong bia build command Render neu chua thay config/deploy script that trong codebase.
