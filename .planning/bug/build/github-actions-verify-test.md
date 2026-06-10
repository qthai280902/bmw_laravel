# BUG-CI-001 - GitHub Actions Verify & Test fail sau rename repo

## Ngay audit/fix

2026-06-10.

## Workflow

- File: `.github/workflows/verify.yml`.
- Job: `laravel-tests`.
- Runner: `ubuntu-latest`.
- Lenh chinh: `composer install`, `npm install`, copy `.env.example`, `php artisan key:generate`, `npm run build`, `php artisan test`.
- Workflow chay full suite bang `php artisan test`, khong phai focused tests.

## Root cause

- Workflow khong hardcode `tmdt_laravel`.
- CI fail tai buoc full suite vi test cu auth/register/settings khong khop app hien tai:
  - Login redirect that la `admin.products.index`, khong phai `/dashboard`.
  - Public `/register` dang tat va tra 404.
  - Route settings that la `/profile` va `/password`, khong phai `/settings/profile` va `/settings/password`.
- Local Git remote truoc fix van tro repo cu `https://github.com/qthai280902/tmdt_laravel.git`.

## Files da sua

- `tests/Feature/Auth/AuthenticationTest.php`.
- `tests/Feature/Auth/RegistrationTest.php`.
- `tests/Feature/Settings/PasswordUpdateTest.php`.
- `tests/Feature/Settings/ProfileUpdateTest.php`.
- `README.md`.
- `package-lock.json`.
- `.planning/bug/build/php-artisan-test.md`.
- `.planning/bug/BUG-INDEX.md`.
- `.planning/STATE.md`.
- `.planning/kientruc/TESTING.md`.

## Ket qua local

- `composer install --no-interaction --prefer-dist --optimize-autoloader`: pass.
- `php artisan config:clear`: pass.
- `php artisan view:clear`: pass.
- `php artisan view:cache`: pass.
- `vendor/bin/pint --dirty --format agent`: pass.
- `vendor/bin/pint --test`: pass.
- `npm.cmd run build`: pass.
- `php artisan test --compact`: pass, 44 tests / 628 assertions.

## Rename follow-up

- Local Git remote da doi sang `https://github.com/qthai280902/bmw_laravel.git`.
- `public/storage` hien van la junction tro `C:\Users\thaib\Downloads\tmdt_laravel\storage\app\public`; day la local filesystem issue, chua sua trong task CI nay.
- `files.txt` la tracked path dump cu va con nhieu path `tmdt_laravel`; khong duoc workflow su dung.

## Trang thai

Fixed / Verified local. Can commit va push de GitHub Actions chay lai tren remote moi.
