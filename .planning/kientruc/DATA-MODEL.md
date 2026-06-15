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
- Phase 15 ArticleSeeder seeds 12 public SEO sample articles, 2 per configured category.
- Phase 15 ArticleSeeder drafts Browser QA title/slug patterns without deleting records.
- Phase 15 adds `Article::editorialImageUrl()` fallback by category.

## site_settings

Them trong Phase 15 de luu cau hinh giao dien nho gon cho public forms.

Cot chinh:

- `key` unique.
- `value` nullable text.
- `type` nullable string.
- timestamps.

Keys hien co:

- `public_form_background_image`

Ghi nhan:

- Upload background duoc luu tren disk `public`, folder `site-settings`.
- Neu khong co setting, fallback la `images/cars/330i/lifestyle-showroom.png`.

## agent_conversations

Them trong Phase 16 qua Laravel AI SDK migration.

Cot chinh:

- `id` string UUID-style primary key.
- `user_id` nullable.
- `title`.
- timestamps.

Ghi nhan:

- Table name lay tu `config('ai.conversations.tables.conversations')`, default `agent_conversations`.
- Public widget Phase 16 currently uses stateless prompt calls and tests do not require persisted real provider conversations.

## agent_conversation_messages

Them trong Phase 16 qua Laravel AI SDK migration.

Cot chinh:

- `id` string UUID-style primary key.
- `conversation_id`.
- `user_id` nullable.
- `agent`.
- `role`.
- `content`.
- `attachments`.
- `tool_calls`.
- `tool_results`.
- `usage`.
- `meta`.
- timestamps.

Ghi nhan:

- Table name lay tu `config('ai.conversations.tables.messages')`, default `agent_conversation_messages`.
- Do not store appointment/accessory order/customer PII in AI prompt context without a separate approved privacy design.

## categories

Dung de phan loai/dong xe/segment san pham. Chi tiet cot can xem migration/model truoc khi sua.

## users

User co relation `appointments()`.

## product_images

Product co relation `images()` va `primaryImage()`. Chi tiet cot can xem migration/model truoc khi sua.
