# HƯỚNG DẪN MỞ RỘNG CƠ SỞ DỮ LIỆU (EXTENSION NOTES)
**Hệ thống Quản lý Vận hành Chuỗi Quán Phở (MESA POS & Operation System)**

---

Tài liệu này hướng dẫn cách mở rộng các tính năng mới trong tương lai **mà không làm phá vỡ cấu trúc bảng hiện tại (Zero Breaking Changes)**.

---

## 1. Mở Rộng Phạm Vi Phân Quyền (`user_roles`)
- **Hiện tại:** Hỗ trợ phạm vi `ALL` (toàn hệ thống) và `BRANCH` (chi nhánh cụ thể).
- **Mở rộng Vùng miền (`REGION`) hoặc Thương hiệu (`BRAND`):**
  1. Bổ sung giá trị vào `ScopeType` enum: `BRAND`, `REGION`.
  2. Cột `scope_id` trong `user_roles` được thiết kế dạng **tham chiếu đa hình số học (polymorphic ID)** không tạo FK cứng:
     - Khi `scope_type = 'BRANCH'`: `scope_id` lưu `branches.id`.
     - Khi `scope_type = 'BRAND'`: `scope_id` lưu `brands.id`.
     - Khi `scope_type = 'REGION'`: `scope_id` lưu `regions.id`.
  3. Cột ảo `scope_key` (`IFNULL(scope_id, 0)`) cùng chỉ mục `UNIQUE (user_id, role_id, scope_type, scope_key)` tự động hỗ trợ đầy đủ các phạm vi mới.

---

## 2. Mở Rộng Kênh Bán Hàng & Đối Tác Giao Hàng (`orders.channel`)
- **Hiện tại:** Hỗ trợ `dine_in` (ăn tại chỗ) và `takeaway` (mang về).
- **Mở rộng:** Thêm các kênh bán hàng như `delivery` (giao hàng riêng), `grab_food`, `shopee_food`, `be_food`:
  1. Thêm giá trị vào `OrderChannel` enum: `delivery`, `grab_food`, `shopee_food`...
  2. Bổ sung bảng vệ tinh `order_deliveries` (1-1 với `orders`):
     - `order_id` FK(orders)
     - `partner_code` (GRAB, SHOPEE...)
     - `partner_order_code` (Mã đơn đối tác)
     - `shipper_name`, `shipper_phone`
     - `delivery_fee`, `delivery_address`
     - `tracking_url`
  3. Không cần sửa cấu trúc bảng `orders`.

---

## 3. Mở Rộng Quản Lý Khách Hàng Thân Thiết (CRM & Loyalty)
- Bổ sung các bảng độc lập:
  - `customers`: `id`, `phone` UNIQUE, `full_name`, `email`, `loyalty_tier` (Silver/Gold/Diamond), `current_points`.
  - `customer_point_history`: `id`, `customer_id` FK, `order_id` FK nullable, `points_delta`, `reason`.
- Liên kết với đơn hàng: Bổ sung cột nullable `customer_id` FK(`customers`) vào bảng `orders`.

---

## 4. Mở Rộng Khuyến Mãi & Voucher (Promotions & Discounts)
- Bổ sung các bảng độc lập:
  - `promotions`: `id`, `code` UNIQUE, `name`, `discount_type` (percent, fixed_amount), `discount_value`, `min_order_amount`, `valid_from`, `valid_to`.
  - `order_promotions`: `id`, `order_id` FK, `promotion_id` FK, `discount_applied_amount`.
- Bảng `orders` đã có sẵn cột `discount_amount` để lưu tổng tiền giảm giá được áp dụng.

---

## 5. Mở Rộng Đặt Bàn Trước (Table Reservations)
- Bổ sung bảng `reservations`:
  - `id`, `branch_id` FK, `table_id` FK nullable, `customer_name`, `customer_phone`, `guest_count`, `reservation_time`, `status` (`pending`, `confirmed`, `seated`, `cancelled`), `deposit_amount`.
- Khi khách đến quán nhận bàn: Gán `table_id` vào `dining_sessions`, chuyển trạng thái bàn trong `dining_tables` sang `occupied` và cập nhật reservation thành `seated`.

---

## 6. Mở Rộng Thanh Toán Trả Góp / Ví Điện Tử & Hoàn Tiền (Refunds)
- Bảng `payments` đã được thiết kế sẵn cho quan hệ **1 Order - Nhiều Payments**:
  - Khách có thể thanh toán một phần bằng Tiền mặt và phần còn lại bằng Quét mã QR (`payments` lưu 2 dòng).
  - Cột `gateway_txn_id UNIQUE` chống thanh toán trùng lặp khi nhận Webhook từ Cổng thanh toán (VNPAY, MoMo, ZaloPay).
  - Khi hoàn tiền: Thêm dòng mới trong `payments` với số tiền âm hoặc tạo bản ghi có `status = 'refunded'`.
