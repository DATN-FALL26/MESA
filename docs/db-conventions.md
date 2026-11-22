# QUY ƯỚC THIẾT KẾ CƠ SỞ DỮ LIỆU (DATABASE CONVENTIONS)
**Hệ thống Quản lý Vận hành Chuỗi Quán Phở (MESA POS & Operation System)**

---

## 1. Quy ước Đặt tên (Naming Conventions)
- **Tên bảng:** Viết bằng tiếng Anh, danh từ số nhiều, `snake_case` (ví dụ: `branches`, `dining_tables`, `menu_items`, `purchase_order_items`).
- **Tên cột:** Viết bằng tiếng Anh, `snake_case` (ví dụ: `employee_code`, `base_price`, `prep_time_seconds`).
- **Khóa chính (Primary Key):**
  - Mọi bảng độc lập sử dụng `id` kiểu `BIGINT UNSIGNED AUTO_INCREMENT`.
  - Không sử dụng khóa chính phức hợp (composite PK) trên các bảng mà Eloquent thao tác CRUD trực tiếp; thay vào đó sử dụng khóa thay thế `id` kèm chỉ mục `UNIQUE` đa cột.
  - Ngoại lệ duy nhất cho composite PK là các bảng pivot nhiều-nhiều thuần túy (`belongsToMany`): `role_permissions`, `menu_item_modifier_groups`, `notification_reads`.
- **Khóa ngoại (Foreign Key):**
  - Đặt tên theo mẫu `[tên_bảng_số_ít]_id` (ví dụ: `branch_id`, `user_id`, `menu_item_id`).
  - Kiểu dữ liệu luôn là `BIGINT UNSIGNED`, khớp hoàn toàn với `id` của bảng cha.
  - Mặc định sử dụng hành vi `ON DELETE RESTRICT` để bảo vệ toàn vẹn dữ liệu nghiệp vụ.
  - Chỉ sử dụng `ON DELETE CASCADE` cho các bảng chi tiết con thuần túy phụ thuộc hoàn toàn vào bảng cha: `role_permissions`, `menu_item_modifier_groups`, `recipe_items`, `purchase_order_items`, `stock_adjustment_items`, `notification_reads`.
  - Riêng bảng `audit_logs` **hoàn toàn không tạo khóa ngoại vật lý** (Foreign Key Constraint) để đảm bảo bản ghi nhật ký kiểm toán là bất biến, không bị lỗi khi người dùng hoặc chi nhánh bị xóa mềm.

---

## 2. Tiêu chuẩn Kiểu dữ liệu (Data Types)
- **Tiền tệ (Currency - VND):** Sử dụng `DECIMAL(15, 0)`. Tuyệt đối không dùng `FLOAT` hay `DOUBLE` để tránh sai số dấu phẩy động.
- **Đơn giá vốn / Giá nhập kho (Unit Cost):** Sử dụng `DECIMAL(15, 2)` để hỗ trợ tính toán giá vốn bình quân gia quyền chính xác.
- **Số lượng (Quantity):** Sử dụng `DECIMAL(15, 3)` cho mọi định lượng kho và nguyên liệu (đáp ứng đơn vị gram `g`, mililit `ml`, cái lẻ `0.5 cái`).
- **Chuỗi trạng thái & Phân loại (Enums):**
  - Sử dụng kiểu `ENUM` trong MySQL, sinh tự động từ `XxxEnum::values()` của class enum PHP tương ứng trong namespace `App\Enums`.
  - Tuyệt đối không hardcode chuỗi trong migration hoặc mã nguồn ứng dụng.
- **Thời gian (Timestamps & DateTime):**
  - Cấu hình múi giờ hệ thống: `Asia/Ho_Chi_Minh` (`config/app.php`).
  - Sử dụng kiểu `DATETIME` (`$table->dateTime(...)`) cho toàn bộ các cột thời gian thay vì `TIMESTAMP` để tránh giới hạn năm 2038 của MySQL.
  - Mọi bảng đều có cặp cột `created_at` và `updated_at` (sử dụng `$table->dateTime('created_at')` / `updated_at`), ngoại trừ các bảng ghi nhận sự kiện 1 lần chỉ có `created_at` (`audit_logs`, `order_status_history`, `stock_movements`, `notifications`) hoặc bảng pivot `[no ts]` (`permissions`, `role_permissions`, `menu_item_modifier_groups`, `notification_reads`).

---

## 3. Xóa mềm (Soft Deletes)
- Cơ chế xóa mềm (`deleted_at`) chỉ áp dụng cho 6 danh mục cốt lõi cần bảo toàn lịch sử tham chiếu:
  1. `branches` (Chi nhánh)
  2. `users` (Tài khoản nhân viên)
  3. `areas` (Khu vực bàn)
  4. `dining_tables` (Bàn ăn)
  5. `menu_items` (Món ăn thực đơn)
  6. `ingredients` (Nguyên vật liệu)
- Tất cả các bảng giao dịch lịch sử (`orders`, `payments`, `stock_movements`, `invoices`...) **không sử dụng soft delete**; thay vào đó kiểm soát trạng thái thông qua cột `status` (`cancelled`, `void`, `refunded`...).

---

## 4. Kỹ thuật Sử dụng Generated Column (Cột Ảo Lưu Trữ)
Do MySQL xem mỗi giá trị `NULL` là một thực thể phân biệt (hai bản ghi cùng có cột là `NULL` sẽ không vi phạm ràng buộc `UNIQUE`), hệ thống áp dụng kỹ thuật **Generated Stored Column** để xử lý các ràng buộc toàn vẹn phức tạp:

1. **Ràng buộc Duy nhất khi Chưa Xóa Mềm:**
   - Áp dụng trên `areas` và `dining_tables`.
   - Cột ảo: `active_flag TINYINT GENERATED ALWAYS AS (IF(deleted_at IS NULL, 1, NULL)) STORED`.
   - Chỉ mục: `UNIQUE (branch_id, code, active_flag)`.
   - *Ý nghĩa:* Cho phép tạo lại bàn hoặc khu vực trùng mã `code` nếu bàn cũ đã bị xóa mềm (`active_flag = NULL`), nhưng chặn hoàn toàn việc trùng mã giữa các bàn đang hoạt động (`active_flag = 1`).

2. **Ràng buộc Một Bàn Chỉ Thuộc Tối Đa Một Lượt Dùng Đang Mở:**
   - Áp dụng trên `session_tables`.
   - Cột ảo: `open_guard BIGINT UNSIGNED GENERATED ALWAYS AS (IF(left_at IS NULL, table_id, NULL)) STORED`.
   - Chỉ mục: `UNIQUE (open_guard)`.
   - *Ý nghĩa:* Nếu bàn chưa rời đi (`left_at IS NULL`), `open_guard = table_id`, ngăn chặn không cho bàn này được gán vào bất kỳ `dining_session` nào khác đang mở. Khi khách rời bàn (`left_at` được gán thời gian), `open_guard` trở thành `NULL`, giải phóng bàn cho lượt khách tiếp theo.

3. **Ràng buộc Gán Quyền Không Trùng Phạm Vi Toàn Hệ Thống:**
   - Áp dụng trên `user_roles`.
   - Cột ảo: `scope_key BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(scope_id, 0)) STORED`.
   - Chỉ mục: `UNIQUE (user_id, role_id, scope_type, scope_key)`.
   - Kèm ràng buộc `CHECK ((scope_type = 'ALL' AND scope_id IS NULL) OR (scope_type <> 'ALL' AND scope_id IS NOT NULL))`.

4. **Ràng buộc Phiên Bản Công Thức Mặc Định:**
   - Áp dụng trên `recipes`.
   - Cột ảo: `variant_key BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(variant_id, 0)) STORED`.
   - Chỉ mục: `UNIQUE (menu_item_id, variant_key, version)`.

---

## 5. Nguyên Tắc Sổ Cái Kho (Inventory Ledger Principle)
- **`stock_movements` là Nguồn Sự Thật Duy Nhất (Single Source of Truth):**
  - Mọi biến động tăng/giảm kho đều phải được ghi nhận dưới dạng một bản ghi phát sinh (dương = nhập, âm = xuất).
  - Bảng `stock_movements` là bất biến: chỉ thêm dòng mới (`INSERT`), **tuyệt đối không `UPDATE` hoặc `DELETE`**.
  - Cột `idempotency_key UNIQUE` bảo vệ chống ghi đè hoặc trừ kho trùng lặp khi retry API/job.
- **`stock_levels` là Bảng Cache Hiệu Năng:**
  - Cung cấp số tồn tức thời (`quantity`) và giá vốn bình quân (`avg_cost`) để tra cứu nhanh trên POS và cảnh báo AI.
  - Số liệu trong `stock_levels` luôn được đồng bộ hoặc tái tính toán dựa trên tổng phát sinh từ `stock_movements`.

---

## 6. Nguyên Tắc Chụp Ảnh Dữ Liệu (Data Snapshot Principle)
- Khi gọi món (`order_items`), thông tin tên món, tên biến thể, đơn giá tại thời điểm khách gọi món được lưu trực tiếp vào các cột snapshot:
  - `item_name_snapshot`
  - `variant_name_snapshot`
  - `unit_price_snapshot`
  - `name_snapshot` và `price_snapshot` (trong `order_item_modifiers`)
- *Ý nghĩa:* Khi nhà hàng thay đổi tên món hoặc điều chỉnh bảng giá thực đơn trong tương lai, toàn bộ đơn hàng cũ và báo cáo doanh thu lịch sử vẫn giữ nguyên giá trị thực tế tại thời điểm giao dịch phát sinh.
