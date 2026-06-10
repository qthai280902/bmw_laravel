# Routing

## Public routes

- `/`
- `/catalog`
- `/catalog/{product:slug}`
- `/compare`
- `/accessories`
- `/offers`
- `/privacy-policy`
- `/contact`
- `/booking`
- `/booking/success`
- `/services`
- `/experiences`
- `/api/products-by-category`

## Product detail route

- URI: `/catalog/{product}`
- Controller: `App\Http\Controllers\ProductController@show`
- Name: `products.show`
- Middleware:
  - `web`
- View: `resources/views/products/show.blade.php`
- Layout: `<x-app-layout>`
- Phase 11 khong doi URL, route name hay booking/compare flow.

## Auth routes

- `/login`
- `/forgot-password`
- `/reset-password/{token}`
- `/verify-email`
- `/confirm-password`
- `/password`
- `/logout`

## Dashboard route

- URI: `/dashboard`
- Controller: `App\Http\Controllers\Admin\DashboardController@index`
- Name: `dashboard`
- Middleware:
  - `web`
  - `auth`
  - `verified`
  - `admin`

## Admin routes

Admin routes dung prefix `/admin`, middleware `auth` va `admin`.

- `admin/products`
- `admin/categories`
- `admin/appointments`
- `admin/users`
- `admin/customers`

## Appointments routes

- Public booking: `/booking`, `/booking/success`.
- Auth appointment list: `/appointments`.
- Admin appointments: `/admin/appointments`, update status qua PUT/PATCH.

## Route/test chua dong bo

- Test cu con expected login redirect `/dashboard`, trong khi auth controller hien redirect `/admin/products`.
- Test cu goi `/register`, nhung route register dang bi comment.
- Test cu goi `/settings/profile` va `/settings/password`, nhung route that hien la `/profile` va `/password`.
