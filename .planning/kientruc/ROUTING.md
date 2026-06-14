# Routing

## Public routes

- `/`
- `/catalog`
- `/catalog/{product:slug}`
- `/compare`
- `/accessories`
- `/accessories/{product:slug}/order`
- `/tim-hieu-them`
- `/tim-hieu-them/{article:slug}`
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
- Phase 12 tiep tuc giu route, route name va controller flow.
- Phase 12 chi branch CTA trong UI theo `Product::type`.

## Phase 12 public routing notes

- `/accessories` van reuse `ProductController@index` voi route default `type=accessory`.
- Car/motorbike CTA van dung:
  - `appointments.create` voi `type=test_drive`
  - `appointments.create` voi `type=quote`
  - `products.compare`
- Compare route van la `/compare?ids=...`.

## Phase 13 public routing notes

- Accessory order dung route rieng:
  - `accessory-orders.create`: `GET /accessories/{product:slug}/order`.
  - `accessory-orders.store`: `POST /accessories/{product:slug}/order`.
- Accessory detail/card CTA khong dung appointment quote de dat hang nua.
- Accessory detail khong hien test-drive route va khong hien compare button.
- Vehicle compare bo qua accessory IDs.
- Public booking category flow khong dua `phu_kien` vao `api/products-by-category`.

## Phase 14 public routing notes

- Article public routes:
  - `articles.index`: `GET /tim-hieu-them`.
  - `articles.show`: `GET /tim-hieu-them/{article:slug}`.
- Public article show dung route model binding theo `slug`.
- Controller chi hien published articles; draft direct URL tra 404.
- Homepage lay latest published articles trong route `/` closure va truyen vao view.

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
- `admin/accessory-orders`
- `admin/articles`
- `admin/users`
- `admin/customers`

## Admin article routes

- `admin.articles.index`: `GET /admin/articles`.
- `admin.articles.create`: `GET /admin/articles/create`.
- `admin.articles.store`: `POST /admin/articles`.
- `admin.articles.edit`: `GET /admin/articles/{article}/edit`.
- `admin.articles.update`: `PUT/PATCH /admin/articles/{article}`.
- `admin.articles.destroy`: `DELETE /admin/articles/{article}`.
- Middleware:
  - `web`.
  - `auth`.
  - `admin`.

## Appointments routes

- Public booking: `/booking`, `/booking/success`.
- Auth appointment list: `/appointments`.
- Admin appointments: `/admin/appointments`, update status qua PUT/PATCH.

## Route/test chua dong bo

- Test cu con expected login redirect `/dashboard`, trong khi auth controller hien redirect `/admin/products`.
- Test cu goi `/register`, nhung route register dang bi comment.
- Test cu goi `/settings/profile` va `/settings/password`, nhung route that hien la `/profile` va `/password`.
