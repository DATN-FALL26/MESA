# TỪ ĐIỂN DỮ LIỆU (DATA DICTIONARY)
**Hệ thống Quản lý Vận hành Chuỗi Quán Phở (MESA POS & Operation System)**

> Tài liệu được sinh tự động từ cấu trúc cơ sở dữ liệu thực tế (`information_schema`).
> Tổng số bảng nghiệp vụ: **51 bảng**.

---

## MỤC LỤC BẢNG DỮ LIỆU

### A. TỔ CHỨC VÀ PHÂN QUYỀN
1. [**`branches`**](#branches) – Chi nhánh chuỗi quán phở
2. [**`departments`**](#departments) – Phòng ban nội bộ
3. [**`users`**](#users) – Tài khoản nhân viên hệ thống
4. [**`roles`**](#roles) – Vai trò chức vụ hệ thống
5. [**`permissions`**](#permissions) – Danh mục quyền hạn chi tiết
6. [**`role_permissions`**](#role-permissions) – Bảng phân quyền cho vai trò
7. [**`user_roles`**](#user-roles) – Gán chức vụ cho nhân viên theo phạm vi
8. [**`audit_logs`**](#audit-logs) – Nhật ký kiểm toán hệ thống

### B. BÀN VÀ LƯỢT DÙNG BỮA
9. [**`areas`**](#areas) – Khu vực bàn ăn trong chi nhánh
10. [**`dining_tables`**](#dining-tables) – Bàn ăn tại chi nhánh
11. [**`dining_sessions`**](#dining-sessions) – Lượt khách dùng bữa tại quán
12. [**`session_tables`**](#session-tables) – Chi tiết bàn gắn với lượt dùng bữa

### C. THỰC ĐƠN & TÙY CHỌN
13. [**`categories`**](#categories) – Danh mục thực đơn
14. [**`menu_items`**](#menu-items) – Món trong thực đơn
15. [**`item_variants`**](#item-variants) – Biến thể kích cỡ món ăn
16. [**`modifier_groups`**](#modifier-groups) – Nhóm tùy chọn món ăn
17. [**`modifiers`**](#modifiers) – Tùy chọn chi tiết món ăn
18. [**`menu_item_modifier_groups`**](#menu-item-modifier-groups) – Gán nhóm tùy chọn cho món ăn
19. [**`branch_menu_items`**](#branch-menu-items) – Cấu hình món ăn theo chi nhánh

### D. GỌI MÓN (ORDER)
20. [**`orders`**](#orders) – Đơn gọi món
21. [**`order_batches`**](#order-batches) – Đợt gọi món
22. [**`order_items`**](#order-items) – Chi tiết món gọi trong đơn
23. [**`order_item_modifiers`**](#order-item-modifiers) – Tùy chọn kèm theo món gọi
24. [**`order_status_history`**](#order-status-history) – Lịch sử thay đổi trạng thái đơn và món

### E. BẾP, KDS VÀ IN ẤN
25. [**`printers`**](#printers) – Thiết bị máy in tại chi nhánh
26. [**`stations`**](#stations) – Quầy chế biến tại chi nhánh (KDS)
27. [**`kitchen_tickets`**](#kitchen-tickets) – Phiếu điều phối chế biến bếp
28. [**`kitchen_ticket_items`**](#kitchen-ticket-items) – Món cần chế biến trong phiếu bếp
29. [**`print_jobs`**](#print-jobs) – Hàng đợi lệnh in ấn

### F. THANH TOÁN VÀ HÓA ĐƠN
30. [**`payments`**](#payments) – Giao dịch thanh toán
31. [**`invoices`**](#invoices) – Hóa đơn thanh toán

### G. KHO VÀ ĐỊNH LƯỢNG
32. [**`ingredients`**](#ingredients) – Nguyên liệu và bán thành phẩm
33. [**`warehouses`**](#warehouses) – Kho lưu trữ nguyên liệu
34. [**`stock_levels`**](#stock-levels) – Mức tồn kho tức thời (bảng cache)
35. [**`stock_movements`**](#stock-movements) – Sổ cái biến động kho (chỉ thêm không sửa/xóa)
36. [**`suppliers`**](#suppliers) – Nhà cung cấp nguyên vật liệu
37. [**`recipes`**](#recipes) – Công thức định lượng món ăn
38. [**`recipe_items`**](#recipe-items) – Chi tiết nguyên liệu trong công thức
39. [**`purchase_orders`**](#purchase-orders) – Phiếu đặt và nhập hàng từ nhà cung cấp
40. [**`purchase_order_items`**](#purchase-order-items) – Chi tiết mặt hàng trong phiếu nhập
41. [**`stock_adjustments`**](#stock-adjustments) – Phiếu kiểm kê và điều chỉnh kho
42. [**`stock_adjustment_items`**](#stock-adjustment-items) – Chi tiết mặt hàng kiểm kê điều chỉnh

### H. AI VÀ PHÊ DUYỆT
43. [**`ai_runs`**](#ai-runs) – Phiên thực thi mô hình phân tích / dự báo AI
44. [**`ai_forecasts`**](#ai-forecasts) – Dự báo nhu cầu món ăn hoặc nguyên liệu từ AI
45. [**`ai_alerts`**](#ai-alerts) – Cảnh báo thông minh từ phân hệ AI
46. [**`approval_requests`**](#approval-requests) – Yêu cầu phê duyệt nghiệp vụ và đề xuất AI

### I. HẠ TẦNG VÀ BÁO CÁO
47. [**`document_sequences`**](#document-sequences) – Quản lý sinh số chứng từ không trùng lặp
48. [**`notifications`**](#notifications) – Thông báo hệ thống phục vụ cơ chế polling
49. [**`notification_reads`**](#notification-reads) – Trạng thái đã đọc thông báo của người dùng
50. [**`daily_sales_summary`**](#daily-sales-summary) – Tổng hợp doanh số bán hàng hàng ngày (Dashboard)
51. [**`daily_item_sales`**](#daily-item-sales) – Báo cáo thống kê món bán hàng ngày (Món bán chạy)

---

## A. TỔ CHỨC VÀ PHÂN QUYỀN

### 1. Bảng `branches`
- **Mô tả:** Chi nhánh chuỗi quán phở
- **Tên hằng:** `ConstantHelper::TABLE_BRANCHES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh chi nhánh |
| 2 | `code` | `varchar(20)` | Không | *Không có* | UNIQUE | Mã chi nhánh duy nhất (ví dụ: PHO01) |
| 3 | `name` | `varchar(150)` | Không | *Không có* | - | Tên chi nhánh |
| 4 | `address` | `varchar(255)` | Có | NULL | - | Địa chỉ chi nhánh |
| 5 | `phone` | `varchar(20)` | Có | NULL | - | Số điện thoại liên hệ |
| 6 | `timezone` | `varchar(50)` | Không | `Asia/Ho_Chi_Minh` | - | Múi giờ hoạt động |
| 7 | `status` | `enum('active','inactive')` | Không | `active` | - | Trạng thái hoạt động |
| 8 | `opened_at` | `date` | Có | NULL | - | Ngày chính thức khai trương |
| 9 | `settings` | `json` | Có | NULL | - | Cấu hình riêng ghi đè theo chi nhánh |
| 10 | `deleted_at` | `timestamp` | Có | NULL | - | Thời điểm xóa mềm |
| 11 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 12 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 2. Bảng `departments`
- **Mô tả:** Phòng ban nội bộ
- **Tên hằng:** `ConstantHelper::TABLE_DEPARTMENTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh phòng ban |
| 2 | `code` | `varchar(30)` | Không | *Không có* | UNIQUE | Mã phòng ban (ví dụ: SERVICE, KITCHEN) |
| 3 | `name` | `varchar(100)` | Không | *Không có* | - | Tên phòng ban |
| 4 | `sort_order` | `int` | Không | `0` | - | Thứ tự sắp xếp hiển thị |
| 5 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái hoạt động |
| 6 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 7 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 3. Bảng `users`
- **Mô tả:** Tài khoản nhân viên hệ thống
- **Tên hằng:** `ConstantHelper::TABLE_USERS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh người dùng |
| 2 | `username` | `varchar(50)` | Không | *Không có* | UNIQUE | Tên đăng nhập hệ thống |
| 3 | `employee_code` | `varchar(30)` | Có | NULL | UNIQUE | Mã số nhân viên |
| 4 | `full_name` | `varchar(150)` | Không | *Không có* | - | Họ và tên nhân viên |
| 5 | `email` | `varchar(150)` | Có | NULL | UNIQUE | Địa chỉ thư điện tử |
| 6 | `phone` | `varchar(20)` | Có | NULL | - | Số điện thoại liên lạc |
| 7 | `password` | `varchar(255)` | Không | *Không có* | - | Mật khẩu đăng nhập đã băm |
| 8 | `pin_hash` | `varchar(255)` | Có | NULL | - | Mã PIN đăng nhập nhanh POS |
| 9 | `department_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 10 | `status` | `enum('active','locked','inactive')` | Không | `active` | - | Trạng thái tài khoản người dùng |
| 11 | `last_login_at` | `datetime` | Có | NULL | - | Thời điểm đăng nhập gần nhất |
| 12 | `remember_token` | `varchar(100)` | Có | NULL | - | Token ghi nhớ đăng nhập |
| 13 | `deleted_at` | `timestamp` | Có | NULL | - | Thời điểm xóa mềm |
| 14 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 15 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 4. Bảng `roles`
- **Mô tả:** Vai trò chức vụ hệ thống
- **Tên hằng:** `ConstantHelper::TABLE_ROLES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh vai trò |
| 2 | `code` | `varchar(50)` | Không | *Không có* | UNIQUE | Mã vai trò chức vụ (ví dụ: ADMIN, CASHIER) |
| 3 | `name` | `varchar(100)` | Không | *Không có* | - | Tên vai trò chức vụ |
| 4 | `description` | `varchar(255)` | Có | NULL | - | Mô tả vai trò chức vụ |
| 5 | `is_system` | `tinyint(1)` | Không | `0` | - | Đánh dấu vai trò hệ thống không được xóa |
| 6 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 7 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 5. Bảng `permissions`
- **Mô tả:** Danh mục quyền hạn chi tiết
- **Tên hằng:** `ConstantHelper::TABLE_PERMISSIONS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh quyền hạn |
| 2 | `code` | `varchar(80)` | Không | *Không có* | UNIQUE | Mã định danh quyền hạn duy nhất |
| 3 | `module` | `varchar(50)` | Không | *Không có* | - | Phân hệ / module quản lý |
| 4 | `name` | `varchar(150)` | Không | *Không có* | - | Tên quyền hạn tiếng Việt |
| 5 | `description` | `varchar(255)` | Có | NULL | - | Mô tả chi tiết quyền hạn |

---

### 6. Bảng `role_permissions`
- **Mô tả:** Bảng phân quyền cho vai trò
- **Tên hằng:** `ConstantHelper::TABLE_ROLE_PERMISSIONS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `role_id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính) | - |
| 2 | `permission_id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính) | - |

---

### 7. Bảng `user_roles`
- **Mô tả:** Gán chức vụ cho nhân viên theo phạm vi
- **Tên hằng:** `ConstantHelper::TABLE_USER_ROLES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh gán chức vụ |
| 2 | `user_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `role_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `scope_type` | `enum('ALL','BRANCH')` | Không | *Không có* | INDEX / FK | Phạm vi hiệu lực (ALL, BRANCH) |
| 5 | `scope_id` | `bigint unsigned` | Có | NULL | - | Mã chi nhánh nếu scope là BRANCH, NULL nếu ALL |
| 6 | `scope_key` | `bigint unsigned` | Có | NULL | STORED GENERATED | Khóa hỗ trợ UNIQUE khi scope_id là NULL |
| 7 | `valid_from` | `datetime` | Không | `CURRENT_TIMESTAMP` | DEFAULT_GENERATED | Thời điểm bắt đầu có hiệu lực |
| 8 | `valid_to` | `datetime` | Có | NULL | - | Thời điểm hết hiệu lực |
| 9 | `granted_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 11 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 8. Bảng `audit_logs`
- **Mô tả:** Nhật ký kiểm toán hệ thống
- **Tên hằng:** `ConstantHelper::TABLE_AUDIT_LOGS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh nhật ký kiểm toán |
| 2 | `branch_id` | `bigint unsigned` | Có | NULL | INDEX / FK | Mã chi nhánh phát sinh hành động |
| 3 | `user_id` | `bigint unsigned` | Có | NULL | INDEX / FK | Mã người dùng thực hiện hành động |
| 4 | `action` | `varchar(60)` | Không | *Không có* | - | Tên hành động (create, update, delete, status_change...) |
| 5 | `entity_type` | `varchar(50)` | Không | *Không có* | INDEX / FK | Tên bảng hoặc loại đối tượng bị tác động |
| 6 | `entity_id` | `bigint unsigned` | Có | NULL | - | Mã định danh đối tượng bị tác động |
| 7 | `old_value` | `json` | Có | NULL | - | Dữ liệu trước khi thay đổi |
| 8 | `new_value` | `json` | Có | NULL | - | Dữ liệu sau khi thay đổi |
| 9 | `ip_address` | `varchar(45)` | Có | NULL | - | Địa chỉ IP nguồn |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm ghi nhận nhật ký |

---

## B. BÀN VÀ LƯỢT DÙNG BỮA

### 9. Bảng `areas`
- **Mô tả:** Khu vực bàn ăn trong chi nhánh
- **Tên hằng:** `ConstantHelper::TABLE_AREAS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh khu vực |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `code` | `varchar(20)` | Không | *Không có* | - | Mã khu vực bàn ăn (tiền tố bàn: A, B, VIP...) |
| 4 | `name` | `varchar(100)` | Không | *Không có* | - | Tên hiển thị của khu vực |
| 5 | `default_seats` | `smallint` | Không | `4` | - | Số lượng ghế mặc định của bàn trong khu |
| 6 | `layout_config` | `json` | Có | NULL | - | Cấu hình lưới hiển thị sơ đồ bàn (cols, rows, cell_size...) |
| 7 | `sort_order` | `int` | Không | `0` | - | Thứ tự sắp xếp hiển thị |
| 8 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái hoạt động của khu vực |
| 9 | `deleted_at` | `timestamp` | Có | NULL | - | Thời điểm xóa mềm |
| 10 | `active_flag` | `tinyint` | Có | NULL | STORED GENERATED | Cờ hỗ trợ UNIQUE khi chưa xóa mềm |
| 11 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 12 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 10. Bảng `dining_tables`
- **Mô tả:** Bàn ăn tại chi nhánh
- **Tên hằng:** `ConstantHelper::TABLE_DINING_TABLES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh bàn ăn |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `area_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 4 | `code` | `varchar(20)` | Không | *Không có* | - | Mã bàn ăn (ví dụ: A01, B02) |
| 5 | `seats` | `smallint` | Không | `4` | - | Số lượng ghế |
| 6 | `shape` | `enum('square','round','rect')` | Không | `square` | - | Hình dạng bàn (square, round, rect) |
| 7 | `pos_x` | `int` | Có | NULL | - | Tọa độ cột X trên sơ đồ lưới |
| 8 | `pos_y` | `int` | Có | NULL | - | Tọa độ hàng Y trên sơ đồ lưới |
| 9 | `width` | `int` | Không | `1` | - | Chiều rộng ô bàn |
| 10 | `height` | `int` | Không | `1` | - | Chiều cao ô bàn |
| 11 | `sort_order` | `int` | Không | `0` | - | Thứ tự sắp xếp hiển thị |
| 12 | `status` | `enum('available','occupied','reserved','cleaning')` | Không | `available` | - | Trạng thái bàn hiện tại |
| 13 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái kích hoạt bàn |
| 14 | `deleted_at` | `timestamp` | Có | NULL | - | Thời điểm xóa mềm |
| 15 | `active_flag` | `tinyint` | Có | NULL | STORED GENERATED | Cờ hỗ trợ UNIQUE khi chưa xóa mềm |
| 16 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 17 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 11. Bảng `dining_sessions`
- **Mô tả:** Lượt khách dùng bữa tại quán
- **Tên hằng:** `ConstantHelper::TABLE_DINING_SESSIONS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh lượt dùng bữa |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `opened_by` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `opened_at` | `datetime` | Không | *Không có* | - | Thời điểm mở lượt dùng |
| 5 | `closed_at` | `datetime` | Có | NULL | - | Thời điểm kết thúc lượt dùng |
| 6 | `guest_count` | `smallint` | Không | `1` | - | Số lượng khách của lượt |
| 7 | `status` | `enum('open','closed','cancelled')` | Không | `open` | - | Trạng thái lượt (open, closed, cancelled) |
| 8 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú của lượt dùng bữa |
| 9 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 10 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 12. Bảng `session_tables`
- **Mô tả:** Chi tiết bàn gắn với lượt dùng bữa
- **Tên hằng:** `ConstantHelper::TABLE_SESSION_TABLES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh bàn trong lượt dùng bữa |
| 2 | `session_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `table_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `joined_at` | `datetime` | Không | *Không có* | - | Thời điểm bàn được gán vào lượt |
| 5 | `left_at` | `datetime` | Có | NULL | - | Thời điểm bàn rời khỏi lượt (chuyển bàn, tách bàn) |
| 6 | `open_guard` | `bigint unsigned` | Có | NULL | UNIQUE, STORED GENERATED | Cột ảo đảm bảo một bàn chỉ thuộc tối đa một lượt đang mở |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

## C. THỰC ĐƠN & TÙY CHỌN

### 13. Bảng `categories`
- **Mô tả:** Danh mục thực đơn
- **Tên hằng:** `ConstantHelper::TABLE_CATEGORIES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh danh mục |
| 2 | `parent_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 3 | `name` | `varchar(100)` | Không | *Không có* | - | Tên danh mục |
| 4 | `sort_order` | `int` | Không | `0` | - | Thứ tự sắp xếp hiển thị |
| 5 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái kích hoạt |
| 6 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 7 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 14. Bảng `menu_items`
- **Mô tả:** Món trong thực đơn
- **Tên hằng:** `ConstantHelper::TABLE_MENU_ITEMS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh món ăn |
| 2 | `category_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `sku` | `varchar(40)` | Không | *Không có* | UNIQUE | Mã định danh món duy nhất |
| 4 | `name` | `varchar(150)` | Không | *Không có* | - | Tên món |
| 5 | `description` | `varchar(255)` | Có | NULL | - | Mô tả chi tiết món |
| 6 | `item_type` | `enum('dish','drink','side','retail')` | Không | `dish` | - | Phân loại món (dish, drink, side, retail) |
| 7 | `station_code` | `enum('PHO','DRINK','SIDE')` | Không | *Không có* | - | Mã quầy chế biến (PHO, DRINK, SIDE) |
| 8 | `base_price` | `decimal(15,0)` | Không | *Không có* | - | Giá bán cơ bản (VND) |
| 9 | `tax_rate` | `decimal(5,2)` | Không | `8.00` | - | Thuế suất VAT (%) |
| 10 | `prep_time_seconds` | `int` | Không | `180` | - | Thời gian chế biến tiêu chuẩn tính bằng giây |
| 11 | `image_path` | `varchar(255)` | Có | NULL | - | Đường dẫn ảnh đại diện món |
| 12 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái kinh doanh |
| 13 | `deleted_at` | `timestamp` | Có | NULL | - | Thời điểm xóa mềm |
| 14 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 15 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 15. Bảng `item_variants`
- **Mô tả:** Biến thể kích cỡ món ăn
- **Tên hằng:** `ConstantHelper::TABLE_ITEM_VARIANTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh biến thể món |
| 2 | `menu_item_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `name` | `varchar(50)` | Không | *Không có* | - | Tên kích cỡ / biến thể (Nhỏ, Lớn, Đặc biệt) |
| 4 | `price_delta` | `decimal(15,0)` | Không | `0` | - | Mức chênh lệch giá so với giá gốc (VND) |
| 5 | `is_default` | `tinyint(1)` | Không | `0` | - | Là biến thể mặc định |
| 6 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái kích hoạt |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 16. Bảng `modifier_groups`
- **Mô tả:** Nhóm tùy chọn món ăn
- **Tên hằng:** `ConstantHelper::TABLE_MODIFIER_GROUPS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh nhóm tùy chọn |
| 2 | `name` | `varchar(100)` | Không | *Không có* | - | Tên nhóm tùy chọn (Thêm thịt, Topping, Ghi chú) |
| 3 | `min_select` | `smallint` | Không | `0` | - | Số lượng chọn tối thiểu |
| 4 | `max_select` | `smallint` | Không | `1` | - | Số lượng chọn tối đa |
| 5 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái kích hoạt |
| 6 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 7 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 17. Bảng `modifiers`
- **Mô tả:** Tùy chọn chi tiết món ăn
- **Tên hằng:** `ConstantHelper::TABLE_MODIFIERS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh tùy chọn |
| 2 | `group_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `name` | `varchar(100)` | Không | *Không có* | - | Tên tùy chọn (Thêm thịt tái, Thêm quẩy...) |
| 4 | `price` | `decimal(15,0)` | Không | `0` | - | Giá phụ thu (VND) |
| 5 | `ingredient_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 6 | `ingredient_qty` | `decimal(15,3)` | Có | NULL | - | Định lượng nguyên liệu tiêu hao |
| 7 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái kích hoạt |
| 8 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 9 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 18. Bảng `menu_item_modifier_groups`
- **Mô tả:** Gán nhóm tùy chọn cho món ăn
- **Tên hằng:** `ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `menu_item_id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính) | - |
| 2 | `group_id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính) | - |
| 3 | `sort_order` | `int` | Không | `0` | - | Thứ tự sắp xếp hiển thị |

---

### 19. Bảng `branch_menu_items`
- **Mô tả:** Cấu hình món ăn theo chi nhánh
- **Tên hằng:** `ConstantHelper::TABLE_BRANCH_MENU_ITEMS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh cấu hình món tại chi nhánh |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `menu_item_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `price` | `decimal(15,0)` | Có | NULL | - | Giá bán ghi đè theo chi nhánh (NULL = dùng base_price) |
| 5 | `is_available` | `tinyint(1)` | Không | `1` | - | Trạng thái còn hàng / hết hàng trong ngày tại chi nhánh |
| 6 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 7 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

## D. GỌI MÓN (ORDER)

### 20. Bảng `orders`
- **Mô tả:** Đơn gọi món
- **Tên hằng:** `ConstantHelper::TABLE_ORDERS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh đơn hàng |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `session_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 4 | `order_no` | `varchar(30)` | Không | *Không có* | - | Mã số đơn hàng duy nhất trong chi nhánh |
| 5 | `channel` | `enum('dine_in','takeaway')` | Không | `dine_in` | - | Kênh bán (dine_in, takeaway) |
| 6 | `status` | `enum('draft','sent','in_progress','served','payment_requested','completed','cancelled')` | Không | `draft` | - | Trạng thái đơn hàng |
| 7 | `subtotal` | `decimal(15,0)` | Không | `0` | - | Tổng tiền hàng trước chiết khấu và thuế (VND) |
| 8 | `discount_amount` | `decimal(15,0)` | Không | `0` | - | Số tiền chiết khấu (VND) |
| 9 | `tax_amount` | `decimal(15,0)` | Không | `0` | - | Tiền thuế VAT (VND) |
| 10 | `total_amount` | `decimal(15,0)` | Không | `0` | - | Tổng tiền thanh toán sau thuế và chiết khấu (VND) |
| 11 | `customer_name` | `varchar(150)` | Có | NULL | - | Tên khách hàng |
| 12 | `customer_phone` | `varchar(20)` | Có | NULL | - | Số điện thoại khách hàng |
| 13 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú đơn hàng |
| 14 | `created_by` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 15 | `payment_requested_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 16 | `payment_requested_at` | `datetime` | Có | NULL | - | Thời điểm yêu cầu thanh toán |
| 17 | `cancelled_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 18 | `cancel_reason` | `varchar(255)` | Có | NULL | - | Lý do hủy đơn |
| 19 | `cancelled_at` | `datetime` | Có | NULL | - | Thời điểm hủy đơn |
| 20 | `completed_at` | `datetime` | Có | NULL | - | Thời điểm hoàn thành đơn |
| 21 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 22 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 21. Bảng `order_batches`
- **Mô tả:** Đợt gọi món
- **Tên hằng:** `ConstantHelper::TABLE_ORDER_BATCHES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh đợt gọi món |
| 2 | `order_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `batch_no` | `smallint unsigned` | Không | *Không có* | - | Số thứ tự đợt gọi món trong đơn |
| 4 | `serve_mode` | `enum('together','as_ready')` | Không | *Không có* | - | Cách thức phục vụ (together, as_ready) |
| 5 | `status` | `enum('open','sent','cooking','partially_ready','all_ready','served','cancelled')` | Không | `open` | - | Trạng thái đợt gọi món |
| 6 | `sent_at` | `datetime` | Có | NULL | - | Thời điểm gửi đợt xuống bếp |
| 7 | `target_ready_at` | `datetime` | Có | NULL | - | Thời gian dự kiến hoàn thành toàn đợt |
| 8 | `all_ready_at` | `datetime` | Có | NULL | - | Thời điểm tất cả món trong đợt đã xong |
| 9 | `served_at` | `datetime` | Có | NULL | - | Thời điểm đã phục vụ đợt xong |
| 10 | `released_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 11 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 12 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 22. Bảng `order_items`
- **Mô tả:** Chi tiết món gọi trong đơn
- **Tên hằng:** `ConstantHelper::TABLE_ORDER_ITEMS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh dòng món trong đơn |
| 2 | `order_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `batch_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 4 | `menu_item_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 5 | `variant_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 6 | `item_name_snapshot` | `varchar(150)` | Không | *Không có* | - | Ảnh chụp tên món tại thời điểm gọi |
| 7 | `variant_name_snapshot` | `varchar(50)` | Có | NULL | - | Ảnh chụp tên biến thể tại thời điểm gọi |
| 8 | `unit_price_snapshot` | `decimal(15,0)` | Không | *Không có* | - | Ảnh chụp đơn giá bán tại thời điểm gọi (VND) |
| 9 | `quantity` | `smallint unsigned` | Không | `1` | - | Số lượng món |
| 10 | `line_total` | `decimal(15,0)` | Không | `0` | - | Tổng tiền dòng món sau khi nhân số lượng (VND) |
| 11 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú món |
| 12 | `status` | `enum('pending','sent','preparing','ready','served','cancelled')` | Không | `pending` | - | Trạng thái chế biến món |
| 13 | `cancelled_reason` | `varchar(255)` | Có | NULL | - | Lý do hủy món |
| 14 | `cancelled_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 15 | `cancelled_at` | `datetime` | Có | NULL | - | Thời điểm hủy món |
| 16 | `stock_deducted_at` | `datetime` | Có | NULL | - | Thời điểm đã thực hiện trừ kho |
| 17 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 18 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 23. Bảng `order_item_modifiers`
- **Mô tả:** Tùy chọn kèm theo món gọi
- **Tên hằng:** `ConstantHelper::TABLE_ORDER_ITEM_MODIFIERS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh tùy chọn của món gọi |
| 2 | `order_item_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `modifier_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `name_snapshot` | `varchar(100)` | Không | *Không có* | - | Ảnh chụp tên tùy chọn tại thời điểm gọi |
| 5 | `price_snapshot` | `decimal(15,0)` | Không | `0` | - | Ảnh chụp đơn giá tùy chọn tại thời điểm gọi (VND) |
| 6 | `quantity` | `smallint unsigned` | Không | `1` | - | Số lượng tùy chọn |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 24. Bảng `order_status_history`
- **Mô tả:** Lịch sử thay đổi trạng thái đơn và món
- **Tên hằng:** `ConstantHelper::TABLE_ORDER_STATUS_HISTORY`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh bản ghi lịch sử trạng thái |
| 2 | `order_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `order_item_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 4 | `batch_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 5 | `from_status` | `varchar(20)` | Có | NULL | - | Trạng thái trước khi chuyển đổi |
| 6 | `to_status` | `varchar(20)` | Không | *Không có* | - | Trạng thái sau khi chuyển đổi |
| 7 | `changed_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 8 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú lý do thay đổi |
| 9 | `created_at` | `datetime` | Có | NULL | - | Thời điểm ghi nhận thay đổi |

---

## E. BẾP, KDS VÀ IN ẤN

### 25. Bảng `printers`
- **Mô tả:** Thiết bị máy in tại chi nhánh
- **Tên hằng:** `ConstantHelper::TABLE_PRINTERS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh máy in |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `name` | `varchar(100)` | Không | *Không có* | - | Tên hiển thị máy in |
| 4 | `type` | `enum('kitchen','receipt')` | Không | *Không có* | - | Phân loại máy in (kitchen, receipt) |
| 5 | `connection` | `varchar(150)` | Không | *Không có* | - | Thông số kết nối (IP, LAN, COM...) |
| 6 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái kích hoạt |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 26. Bảng `stations`
- **Mô tả:** Quầy chế biến tại chi nhánh (KDS)
- **Tên hằng:** `ConstantHelper::TABLE_STATIONS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh quầy chế biến (màn hình KDS) |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `station_code` | `enum('PHO','DRINK','SIDE')` | Không | *Không có* | - | Mã phân loại quầy (PHO, DRINK, SIDE) |
| 4 | `name` | `varchar(100)` | Không | *Không có* | - | Tên quầy chế biến |
| 5 | `printer_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 6 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái hoạt động |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 27. Bảng `kitchen_tickets`
- **Mô tả:** Phiếu điều phối chế biến bếp
- **Tên hằng:** `ConstantHelper::TABLE_KITCHEN_TICKETS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh phiếu bếp |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `order_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `batch_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 5 | `station_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 6 | `ticket_no` | `varchar(30)` | Không | *Không có* | - | Số phiếu bếp trong chi nhánh |
| 7 | `status` | `enum('new','preparing','ready','done','cancelled')` | Không | `new` | - | Trạng thái phiếu bếp |
| 8 | `fired_at` | `datetime` | Không | *Không có* | - | Thời điểm kích hoạt phiếu bếp |
| 9 | `ready_at` | `datetime` | Có | NULL | - | Thời điểm hoàn thành tất cả món trong phiếu |
| 10 | `updated_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 11 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 12 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 28. Bảng `kitchen_ticket_items`
- **Mô tả:** Món cần chế biến trong phiếu bếp
- **Tên hằng:** `ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh dòng món trong phiếu bếp |
| 2 | `ticket_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `order_item_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `status` | `enum('new','scheduled','preparing','ready','cancelled')` | Không | `new` | - | Trạng thái chế biến của món |
| 5 | `fire_at` | `datetime` | Có | NULL | - | Thời điểm dự kiến bắt đầu nấu theo lịch |
| 6 | `started_at` | `datetime` | Có | NULL | - | Thời điểm thực tế bắt đầu nấu |
| 7 | `ready_at` | `datetime` | Có | NULL | - | Thời điểm món đã nấu xong |
| 8 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 9 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 29. Bảng `print_jobs`
- **Mô tả:** Hàng đợi lệnh in ấn
- **Tên hằng:** `ConstantHelper::TABLE_PRINT_JOBS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh lệnh in |
| 2 | `printer_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `doc_type` | `enum('kitchen_ticket','invoice','payment_request')` | Không | *Không có* | - | Loại chứng từ in (kitchen_ticket, invoice, payment_request) |
| 4 | `ref_id` | `bigint unsigned` | Không | *Không có* | - | Mã định danh đối tượng cần in |
| 5 | `payload` | `json` | Có | NULL | - | Dữ liệu in ấn dạng JSON |
| 6 | `status` | `enum('queued','printed','failed')` | Không | `queued` | - | Trạng thái lệnh in |
| 7 | `retry_count` | `tinyint unsigned` | Không | `0` | - | Số lần thử in lại khi thất bại |
| 8 | `printed_at` | `datetime` | Có | NULL | - | Thời điểm in thành công |
| 9 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 10 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

## F. THANH TOÁN VÀ HÓA ĐƠN

### 30. Bảng `payments`
- **Mô tả:** Giao dịch thanh toán
- **Tên hằng:** `ConstantHelper::TABLE_PAYMENTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh giao dịch thanh toán |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `order_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `method` | `enum('cash','qr','card')` | Không | *Không có* | - | Phương thức thanh toán (cash, qr, card) |
| 5 | `amount` | `decimal(15,0)` | Không | *Không có* | - | Số tiền thanh toán (VND) |
| 6 | `tendered_amount` | `decimal(15,0)` | Có | NULL | - | Số tiền khách đưa (VND) |
| 7 | `change_amount` | `decimal(15,0)` | Có | NULL | - | Số tiền thừa trả lại khách (VND) |
| 8 | `status` | `enum('pending','success','failed','refunded')` | Không | `pending` | - | Trạng thái thanh toán |
| 9 | `gateway_txn_id` | `varchar(100)` | Có | NULL | UNIQUE | Mã giao dịch từ cổng thanh toán / ngân hàng |
| 10 | `gateway_payload` | `json` | Có | NULL | - | Dữ liệu phản hồi từ cổng thanh toán dạng JSON |
| 11 | `confirmed_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 12 | `paid_at` | `datetime` | Có | NULL | - | Thời điểm thanh toán thành công |
| 13 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 14 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 31. Bảng `invoices`
- **Mô tả:** Hóa đơn thanh toán
- **Tên hằng:** `ConstantHelper::TABLE_INVOICES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh hóa đơn |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `order_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `invoice_no` | `varchar(30)` | Không | *Không có* | - | Mã số hóa đơn |
| 5 | `issued_at` | `datetime` | Không | *Không có* | - | Thời điểm phát hành hóa đơn |
| 6 | `total_amount` | `decimal(15,0)` | Không | *Không có* | - | Tổng tiền trên hóa đơn (VND) |
| 7 | `buyer_info` | `json` | Có | NULL | - | Thông tin người mua / xuất hóa đơn VAT dạng JSON |
| 8 | `status` | `enum('issued','void')` | Không | `issued` | - | Trạng thái hóa đơn (issued, void) |
| 9 | `print_count` | `smallint unsigned` | Không | `0` | - | Số lần đã in hóa đơn |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 11 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

## G. KHO VÀ ĐỊNH LƯỢNG

### 32. Bảng `ingredients`
- **Mô tả:** Nguyên liệu và bán thành phẩm
- **Tên hằng:** `ConstantHelper::TABLE_INGREDIENTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh nguyên liệu |
| 2 | `sku` | `varchar(40)` | Không | *Không có* | UNIQUE | Mã định danh nguyên liệu duy nhất |
| 3 | `name` | `varchar(150)` | Không | *Không có* | - | Tên nguyên liệu |
| 4 | `ingredient_type` | `enum('raw','semi_finished')` | Không | `raw` | - | Phân loại nguyên liệu (raw, semi_finished) |
| 5 | `base_uom` | `varchar(20)` | Không | *Không có* | - | Đơn vị tính cơ sở (g, ml, cái...) |
| 6 | `min_stock` | `decimal(15,3)` | Không | `0.000` | - | Mức tồn kho tối thiểu cảnh báo |
| 7 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái sử dụng |
| 8 | `deleted_at` | `timestamp` | Có | NULL | - | Thời điểm xóa mềm |
| 9 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 10 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 33. Bảng `warehouses`
- **Mô tả:** Kho lưu trữ nguyên liệu
- **Tên hằng:** `ConstantHelper::TABLE_WAREHOUSES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh kho |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `name` | `varchar(100)` | Không | *Không có* | - | Tên kho lưu trữ |
| 4 | `type` | `enum('branch','central')` | Không | `branch` | - | Loại kho (branch, central) |
| 5 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái hoạt động của kho |
| 6 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 7 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 34. Bảng `stock_levels`
- **Mô tả:** Mức tồn kho tức thời (bảng cache)
- **Tên hằng:** `ConstantHelper::TABLE_STOCK_LEVELS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh mức tồn kho |
| 2 | `warehouse_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `ingredient_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `quantity` | `decimal(15,3)` | Không | `0.000` | - | Số lượng tồn hiện tại theo đơn vị cơ sở |
| 5 | `avg_cost` | `decimal(15,2)` | Không | `0.00` | - | Giá vốn bình quân gia quyền (VND) |
| 6 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 7 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 35. Bảng `stock_movements`
- **Mô tả:** Sổ cái biến động kho (chỉ thêm không sửa/xóa)
- **Tên hằng:** `ConstantHelper::TABLE_STOCK_MOVEMENTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh biến động kho |
| 2 | `warehouse_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `ingredient_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `movement_type` | `enum('purchase_in','sale_out','sale_return','adjust','waste')` | Không | *Không có* | - | Loại biến động kho (purchase_in, sale_out, sale_return, adjust, waste) |
| 5 | `quantity` | `decimal(15,3)` | Không | *Không có* | - | Số lượng biến động (dương = nhập, âm = xuất) |
| 6 | `unit_cost` | `decimal(15,2)` | Có | NULL | - | Đơn giá vốn xuất/nhập tại thời điểm biến động |
| 7 | `ref_type` | `varchar(30)` | Có | NULL | INDEX / FK | Loại chứng từ tham chiếu (order_item, purchase_order, stock_adjustment) |
| 8 | `ref_id` | `bigint unsigned` | Có | NULL | - | Mã định danh chứng từ tham chiếu |
| 9 | `idempotency_key` | `varchar(100)` | Có | NULL | UNIQUE | Khóa chống ghi nhận biến động trùng lặp |
| 10 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú biến động kho |
| 11 | `created_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 12 | `created_at` | `datetime` | Có | NULL | - | Thời điểm ghi nhận biến động |

---

### 36. Bảng `suppliers`
- **Mô tả:** Nhà cung cấp nguyên vật liệu
- **Tên hằng:** `ConstantHelper::TABLE_SUPPLIERS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh nhà cung cấp |
| 2 | `name` | `varchar(150)` | Không | *Không có* | - | Tên nhà cung cấp |
| 3 | `contact` | `varchar(150)` | Có | NULL | - | Người liên hệ đại diện |
| 4 | `phone` | `varchar(20)` | Có | NULL | - | Số điện thoại liên hệ |
| 5 | `address` | `varchar(255)` | Có | NULL | - | Địa chỉ nhà cung cấp |
| 6 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái hợp tác hoạt động |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 37. Bảng `recipes`
- **Mô tả:** Công thức định lượng món ăn
- **Tên hằng:** `ConstantHelper::TABLE_RECIPES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh công thức định lượng |
| 2 | `menu_item_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `variant_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 4 | `variant_key` | `bigint unsigned` | Có | NULL | STORED GENERATED | Cột ảo hỗ trợ UNIQUE khi variant_id là NULL |
| 5 | `version` | `int` | Không | `1` | - | Phiên bản công thức định lượng |
| 6 | `is_active` | `tinyint(1)` | Không | `1` | - | Trạng thái kích hoạt công thức |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 38. Bảng `recipe_items`
- **Mô tả:** Chi tiết nguyên liệu trong công thức
- **Tên hằng:** `ConstantHelper::TABLE_RECIPE_ITEMS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh dòng định lượng nguyên liệu |
| 2 | `recipe_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `ingredient_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `quantity` | `decimal(15,3)` | Không | *Không có* | - | Định lượng nguyên liệu tiêu hao theo đơn vị cơ sở (base_uom) |
| 5 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 6 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 39. Bảng `purchase_orders`
- **Mô tả:** Phiếu đặt và nhập hàng từ nhà cung cấp
- **Tên hằng:** `ConstantHelper::TABLE_PURCHASE_ORDERS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh đơn nhập hàng |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `warehouse_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `supplier_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 5 | `po_no` | `varchar(30)` | Không | *Không có* | - | Mã số đơn nhập hàng duy nhất theo chi nhánh |
| 6 | `status` | `enum('draft','pending_approval','approved','received','cancelled')` | Không | `draft` | - | Trạng thái đơn nhập |
| 7 | `source` | `enum('manual','ai_suggestion')` | Không | `manual` | - | Nguồn gốc tạo đơn (manual, ai_suggestion) |
| 8 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú đơn nhập |
| 9 | `created_by` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 10 | `approved_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 11 | `approved_at` | `datetime` | Có | NULL | - | Thời điểm phê duyệt đơn nhập |
| 12 | `received_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 13 | `received_at` | `datetime` | Có | NULL | - | Thời điểm thực tế nhập kho |
| 14 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 15 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 40. Bảng `purchase_order_items`
- **Mô tả:** Chi tiết mặt hàng trong phiếu nhập
- **Tên hằng:** `ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh dòng hàng nhập |
| 2 | `purchase_order_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `ingredient_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `quantity` | `decimal(15,3)` | Không | *Không có* | - | Số lượng đặt mua theo đơn vị cơ sở |
| 5 | `unit_price` | `decimal(15,2)` | Không | `0.00` | - | Đơn giá nhập mua dự kiến (VND) |
| 6 | `received_qty` | `decimal(15,3)` | Không | `0.000` | - | Số lượng thực tế đã nhập kho |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 41. Bảng `stock_adjustments`
- **Mô tả:** Phiếu kiểm kê và điều chỉnh kho
- **Tên hằng:** `ConstantHelper::TABLE_STOCK_ADJUSTMENTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh phiếu kiểm kê / điều chỉnh |
| 2 | `warehouse_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `adjustment_no` | `varchar(30)` | Không | *Không có* | UNIQUE | Mã số phiếu kiểm kê duy nhất |
| 4 | `reason` | `enum('stocktake','waste','damage','correction','other')` | Không | *Không có* | - | Lý do kiểm kê / điều chỉnh (stocktake, waste, damage, correction, other) |
| 5 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú chi tiết |
| 6 | `status` | `enum('draft','pending_approval','approved','rejected')` | Không | `draft` | - | Trạng thái phiếu kiểm kê (draft, pending_approval, approved, rejected) |
| 7 | `created_by` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 8 | `approved_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 9 | `approved_at` | `datetime` | Có | NULL | - | Thời điểm phê duyệt |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 11 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 42. Bảng `stock_adjustment_items`
- **Mô tả:** Chi tiết mặt hàng kiểm kê điều chỉnh
- **Tên hằng:** `ConstantHelper::TABLE_STOCK_ADJUSTMENT_ITEMS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh dòng kiểm kê |
| 2 | `adjustment_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `ingredient_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `system_qty` | `decimal(15,3)` | Không | *Không có* | - | Số lượng sổ sách hệ thống tại thời điểm kiểm |
| 5 | `actual_qty` | `decimal(15,3)` | Không | *Không có* | - | Số lượng thực tế đếm được |
| 6 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú lý do chênh lệch |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

## H. AI VÀ PHÊ DUYỆT

### 43. Bảng `ai_runs`
- **Mô tả:** Phiên thực thi mô hình phân tích / dự báo AI
- **Tên hằng:** `ConstantHelper::TABLE_AI_RUNS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh phiên chạy mô hình AI |
| 2 | `branch_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 3 | `run_type` | `enum('sales_analysis','demand_forecast','shortage_detection')` | Không | *Không có* | - | Loại phân tích AI (sales_analysis, demand_forecast, shortage_detection) |
| 4 | `params` | `json` | Có | NULL | - | Tham số đầu vào của mô hình dạng JSON |
| 5 | `status` | `enum('queued','running','done','failed')` | Không | `queued` | - | Trạng thái phiên chạy |
| 6 | `result_summary` | `json` | Có | NULL | - | Tóm tắt kết quả phân tích / dự báo dạng JSON |
| 7 | `triggered_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 8 | `started_at` | `datetime` | Có | NULL | - | Thời điểm bắt đầu xử lý |
| 9 | `finished_at` | `datetime` | Có | NULL | - | Thời điểm hoàn thành |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 11 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 44. Bảng `ai_forecasts`
- **Mô tả:** Dự báo nhu cầu món ăn hoặc nguyên liệu từ AI
- **Tên hằng:** `ConstantHelper::TABLE_AI_FORECASTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh kết quả dự báo |
| 2 | `ai_run_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 4 | `menu_item_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 5 | `ingredient_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 6 | `forecast_date` | `date` | Không | *Không có* | - | Ngày dự báo |
| 7 | `predicted_qty` | `decimal(15,3)` | Không | *Không có* | - | Số lượng dự báo |
| 8 | `confidence` | `decimal(5,4)` | Có | NULL | - | Độ tin cậy của mô hình (0.0000 - 1.0000) |
| 9 | `model_version` | `varchar(50)` | Không | *Không có* | - | Phiên bản mô hình AI |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 11 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 45. Bảng `ai_alerts`
- **Mô tả:** Cảnh báo thông minh từ phân hệ AI
- **Tên hằng:** `ConstantHelper::TABLE_AI_ALERTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh cảnh báo |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `alert_type` | `enum('low_stock_risk','sales_anomaly')` | Không | *Không có* | - | Loại cảnh báo (low_stock_risk, sales_anomaly) |
| 4 | `severity` | `enum('info','warning','critical')` | Không | `info` | - | Mức độ nghiêm trọng (info, warning, critical) |
| 5 | `ingredient_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 6 | `payload` | `json` | Có | NULL | - | Dữ liệu chi tiết cảnh báo dạng JSON |
| 7 | `status` | `enum('new','acknowledged','resolved')` | Không | `new` | - | Trạng thái xử lý cảnh báo |
| 8 | `acknowledged_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 9 | `resolved_at` | `datetime` | Có | NULL | - | Thời điểm giải quyết xong cảnh báo |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 11 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 46. Bảng `approval_requests`
- **Mô tả:** Yêu cầu phê duyệt nghiệp vụ và đề xuất AI
- **Tên hằng:** `ConstantHelper::TABLE_APPROVAL_REQUESTS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh yêu cầu phê duyệt |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `request_type` | `enum('purchase_order','stock_adjustment','ai_suggestion')` | Không | *Không có* | INDEX / FK | Loại phê duyệt (purchase_order, stock_adjustment, ai_suggestion) |
| 4 | `ref_id` | `bigint unsigned` | Không | *Không có* | - | Mã định danh đối tượng cần duyệt (tham chiếu đa hình, không FK) |
| 5 | `requested_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 6 | `status` | `enum('pending','approved','rejected')` | Không | `pending` | - | Trạng thái phê duyệt (pending, approved, rejected) |
| 7 | `decided_by` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 8 | `decided_at` | `datetime` | Có | NULL | - | Thời điểm ra quyết định |
| 9 | `note` | `varchar(255)` | Có | NULL | - | Ghi chú lý do phê duyệt hoặc từ chối |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 11 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

## I. HẠ TẦNG VÀ BÁO CÁO

### 47. Bảng `document_sequences`
- **Mô tả:** Quản lý sinh số chứng từ không trùng lặp
- **Tên hằng:** `ConstantHelper::TABLE_DOCUMENT_SEQUENCES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh chuỗi số chứng từ |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `doc_type` | `varchar(30)` | Không | *Không có* | - | Loại chứng từ (ORDER, INVOICE, PO...) |
| 4 | `period_key` | `varchar(10)` | Không | `` | - | Khóa chu kỳ thời gian (ví dụ: 20261003) |
| 5 | `prefix` | `varchar(20)` | Không | `` | - | Tiền tố mã chứng từ |
| 6 | `current_no` | `bigint unsigned` | Không | `0` | - | Số thứ tự hiện tại của chuỗi |
| 7 | `padding` | `tinyint unsigned` | Không | `5` | - | Độ dài đệm số (số chữ số 0 phía trước) |
| 8 | `reset_policy` | `enum('never','daily','monthly','yearly')` | Không | `daily` | - | Quy tắc đặt lại chuỗi số |
| 9 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 10 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 48. Bảng `notifications`
- **Mô tả:** Thông báo hệ thống phục vụ cơ chế polling
- **Tên hằng:** `ConstantHelper::TABLE_NOTIFICATIONS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh thông báo |
| 2 | `branch_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 3 | `target_user_id` | `bigint unsigned` | Có | NULL | INDEX / FK | - |
| 4 | `target_role_code` | `varchar(50)` | Có | NULL | - | Nhóm vai trò nhận thông báo trong chi nhánh (ví dụ: KITCHEN, CASHIER) |
| 5 | `type` | `enum('batch_all_ready','payment_request','order_cancelled','low_stock','approval_request','ai_alert','system')` | Không | *Không có* | - | Phân loại thông báo |
| 6 | `title` | `varchar(200)` | Không | *Không có* | - | Tiêu đề thông báo |
| 7 | `body` | `varchar(500)` | Có | NULL | - | Nội dung chi tiết thông báo |
| 8 | `payload` | `json` | Có | NULL | - | Dữ liệu đính kèm thông báo dạng JSON |
| 9 | `expires_at` | `datetime` | Có | NULL | - | Thời điểm hết hạn hiển thị |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm phát hành thông báo |

---

### 49. Bảng `notification_reads`
- **Mô tả:** Trạng thái đã đọc thông báo của người dùng
- **Tên hằng:** `ConstantHelper::TABLE_NOTIFICATION_READS`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `notification_id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính) | - |
| 2 | `user_id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính) | - |
| 3 | `read_at` | `datetime` | Không | *Không có* | - | Thời điểm đọc thông báo |

---

### 50. Bảng `daily_sales_summary`
- **Mô tả:** Tổng hợp doanh số bán hàng hàng ngày (Dashboard)
- **Tên hằng:** `ConstantHelper::TABLE_DAILY_SALES_SUMMARY`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh tổng hợp doanh thu ngày |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `sales_date` | `date` | Không | *Không có* | - | Ngày bán hàng |
| 4 | `order_count` | `int` | Không | `0` | - | Tổng số đơn hoàn tất trong ngày |
| 5 | `guest_count` | `int` | Không | `0` | - | Tổng số lượt khách phục vụ |
| 6 | `gross_revenue` | `decimal(15,0)` | Không | `0` | - | Doanh thu trước chiết khấu (VND) |
| 7 | `discount_amount` | `decimal(15,0)` | Không | `0` | - | Tổng tiền chiết khấu (VND) |
| 8 | `net_revenue` | `decimal(15,0)` | Không | `0` | - | Doanh thu thực tế sau chiết khấu (VND) |
| 9 | `payment_breakdown` | `json` | Có | NULL | - | Cơ cấu doanh thu theo phương thức thanh toán ({cash, qr, card}) |
| 10 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 11 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

### 51. Bảng `daily_item_sales`
- **Mô tả:** Báo cáo thống kê món bán hàng ngày (Món bán chạy)
- **Tên hằng:** `ConstantHelper::TABLE_DAILY_ITEM_SALES`

| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |
| :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| 1 | `id` | `bigint unsigned` | Không | *Không có* | PK (Khóa chính), auto_increment | Mã định danh báo cáo món bán ngày |
| 2 | `branch_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 3 | `sales_date` | `date` | Không | *Không có* | - | Ngày bán hàng |
| 4 | `menu_item_id` | `bigint unsigned` | Không | *Không có* | INDEX / FK | - |
| 5 | `quantity` | `int` | Không | `0` | - | Số lượng món đã bán |
| 6 | `gross_revenue` | `decimal(15,0)` | Không | `0` | - | Tổng doanh thu món trước chiết khấu (VND) |
| 7 | `created_at` | `datetime` | Có | NULL | - | Thời điểm tạo bản ghi |
| 8 | `updated_at` | `datetime` | Có | NULL | - | Thời điểm cập nhật bản ghi |

---

