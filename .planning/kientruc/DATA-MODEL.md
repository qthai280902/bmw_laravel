# Data Model

## appointments

Cot da xac minh tu model/migration:

- `user_id`
- `guest_name`
- `guest_email`
- `guest_phone`
- `product_id`
- `type`
- `appointment_date`
- `status`
- `notes`
- `meta_data`
- `showroom`

Casts:

- `meta_data`: array.
- `type`: enum `AppointmentType`.
- `status`: enum `AppointmentStatus`.
- `appointment_date`: datetime.

Relations:

- `Appointment -> user`
- `Appointment -> product`

## products

Relations:

- `Product -> category`
- `Product -> images`
- `Product -> primaryImage`
- `Product -> accessoryOrders`

Ghi nhan:

- Product co `SoftDeletes`.
- Product co JSON `specifications`.
- Product co scope `active()`.
- Product helpers Phase 13:
  - `isVehicle()`.
  - `canTestDrive()`.
  - `canCompare()`.
  - `canOrderAccessory()`.

## accessory_orders

Them trong Phase 13 de luu dat hang phu kien rieng, khong tron vao `appointments`.

Cot chinh:

- `product_id`
- `customer_name`
- `customer_phone`
- `customer_address`
- `customer_email`
- `quantity`
- `notes`
- `status`
- `admin_notes`
- `confirmed_at`
- timestamps

Status hien co:

- `pending`
- `confirmed`
- `cancelled`
- `completed`

Relations:

- `AccessoryOrder -> product`

## articles

Them trong Phase 14 de quan ly noi dung tin/bai viet cho public "Tim hieu them".

Cot chinh:

- `user_id`
- `title`
- `slug`
- `category`
- `excerpt`
- `body`
- `cover_image`
- `status`
- `published_at`
- `seo_title`
- `seo_description`
- timestamps

Status hien co:

- `draft`
- `published`

Categories hien co:

- `uu-dai-khach-hang`
- `chuong-trinh-ban-hang`
- `su-kien-showroom`
- `trai-nghiem-bmw`
- `dich-vu-hau-mai`
- `tin-tuc-showroom`

Relations:

- `Article -> user`

Ghi nhan:

- Route binding dung `slug`.
- `Article::published()` chi lay bai status `published` va `published_at <= now()`.
- Cover image luu tren disk `public`, path folder `articles`.
- ArticleSeeder dung `updateOrCreate` de tranh duplicate slug.

## categories

Dung de phan loai/dong xe/segment san pham. Chi tiet cot can xem migration/model truoc khi sua.

## users

User co relation `appointments()`.

## product_images

Product co relation `images()` va `primaryImage()`. Chi tiet cot can xem migration/model truoc khi sua.
