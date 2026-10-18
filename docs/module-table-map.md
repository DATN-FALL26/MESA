# BẢN ĐỒ ÁNH XẠ MODULE – BẢNG DỮ LIỆU (MODULE - TABLE MAP)
**Hệ thống Quản lý Vận hành Chuỗi Quán Phở (MESA POS & Operation System)**

---

## 1. DANH SÁCH ACTOR (TÁC NHÂN HỆ THỐNG)

1. **Quản trị viên Hệ thống (System Admin):** Quản lý chi nhánh, phòng ban, phân quyền người dùng, xem toàn bộ nhật ký hệ thống.
2. **Quản lý Chi nhánh (Branch Manager):** Thiết lập sơ đồ bàn, cấu hình menu/máy in cơ sở, duyệt đơn nhập kho/kiểm kê, theo dõi báo cáo, phê duyệt đề xuất AI.
3. **Thu ngân (Cashier):** Quản lý lượt dùng bữa, mở/chuyển bàn, tạo đơn thanh toán, thu tiền (tiền mặt/QR/thẻ), in hóa đơn VAT.
4. **Nhân viên Phục vụ (Waiter):** Tiếp đón khách, mở bàn, gọi món (chia đợt/topping), gửi order xuống bếp, xác nhận phục vụ món, yêu cầu thanh toán.
5. **Nhân viên Bếp / Bar (Kitchen Staff):** Theo dõi màn hình KDS, cập nhật tiến độ chế biến (đang nấu, đã xong từng món/cả đợt), in phiếu bếp, báo hết món tức thời.
6. **Nhân viên Kho (Warehouse Staff):** Tạo phiếu nhập hàng, xuất nguyên liệu, kiểm kê thực tế, quản lý công thức định lượng món.

---

## 2. MA TRẬN 9 MODULE NGHIỆP VỤ VÀ BẢNG DỮ LIỆU LIÊN QUAN

| STT | Phân hệ (Module) | Use Case cốt lõi | Actor chính | Bảng GHI (Write) | Bảng ĐỌC (Read) |
| :---: | :--- | :--- | :--- | :--- | :--- |
| **1** | **Xác thực & Phân quyền** | - Đăng nhập (Mật khẩu / PIN POS)<br>- Gán quyền theo chi nhánh/toàn chuỗi<br>- Ghi nhận nhật ký kiểm toán | Admin, Mọi nhân viên | `users`, `user_roles`, `audit_logs` | `roles`, `permissions`, `role_permissions`, `departments`, `branches` |
| **2** | **Quản lý Bàn & Sơ đồ** | - Vẽ sơ đồ bàn theo tọa độ lưới<br>- Mở lượt ăn, chuyển bàn, gộp bàn | Quản lý, Thu ngân, Phục vụ | `areas`, `dining_tables`, `dining_sessions`, `session_tables` | `branches`, `users` |
| **3** | **Quản lý Thực đơn & Giá** | - Quản lý món, size, topping<br>- Ghi đè giá & bật/tắt món theo chi nhánh | Quản lý, Bếp | `categories`, `menu_items`, `item_variants`, `modifier_groups`, `modifiers`, `menu_item_modifier_groups`, `branch_menu_items` | `ingredients` (khi gán topping trừ kho) |
| **4** | **Gọi món (POS Order)** | - Tạo đơn tại bàn / mang về<br>- Chia đợt gọi món, snapshot giá<br>- Chọn topping & ghi chú chế biến | Phục vụ, Thu ngân | `orders`, `order_batches`, `order_items`, `order_item_modifiers`, `order_status_history` | `dining_sessions`, `menu_items`, `item_variants`, `modifiers`, `users`, `branches` |
| **5** | **Điều phối Bếp & KDS** | - Nhận ticket chế biến theo quầy (Phở/Nước/Món kèm)<br>- Cập nhật trạng thái nấu từng món/cả đợt<br>- Điều phối máy in bếp | Nhân viên Bếp, Phục vụ | `kitchen_tickets`, `kitchen_ticket_items`, `print_jobs`, `order_batches` (cập nhật status), `order_items` (cập nhật status) | `stations`, `printers`, `orders`, `menu_items` |
| **6** | **Thanh toán & Hóa đơn** | - Thu tiền nhiều phương thức (Tiền mặt, QR, Thẻ)<br>- Tách/gộp thanh toán<br>- Xuất & in hóa đơn GTGT | Thu ngân, Khách hàng | `payments`, `invoices`, `orders` (cập nhật hoàn tất), `dining_sessions` (đóng lượt), `session_tables` (giải phóng bàn), `print_jobs` | `branches`, `users`, `document_sequences` |
| **7** | **Quản lý Kho & Định lượng** | - Quản lý BOM định lượng nguyên liệu<br>- Tự động trừ kho khi món ra khỏi bếp<br>- Nhập hàng nhà cung cấp & kiểm kê kho | Nhân viên Kho, Quản lý chi nhánh | `stock_movements`, `stock_levels`, `purchase_orders`, `purchase_order_items`, `stock_adjustments`, `stock_adjustment_items`, `recipes`, `recipe_items` | `ingredients`, `warehouses`, `suppliers`, `menu_items`, `users` |
| **8** | **AI Phân tích & Dự báo** | - Dự báo nhu cầu nguyên liệu ngày tới<br>- Phát hiện bất thường bán hàng<br>- Đề xuất đơn nhập hàng tự động | Quản lý, AI Service | `ai_runs`, `ai_forecasts`, `ai_alerts`, `approval_requests`, `purchase_orders` (khi duyệt gợi ý) | `daily_sales_summary`, `daily_item_sales`, `stock_levels`, `stock_movements`, `menu_items` |
| **9** | **Báo cáo & Hạ tầng** | - Sinh số chứng từ liên tục không trùng<br>- Polling thông báo realtime<br>- Dashboard doanh thu ngày & món bán chạy | Ban giám đốc, Quản lý, Mọi Actor | `document_sequences`, `notifications`, `notification_reads`, `daily_sales_summary`, `daily_item_sales` | `orders`, `payments`, `menu_items`, `branches` |

---

## 3. HƯỚNG DẪN CHIA TASK THEO MODULE CHO NHÓM PHÁT TRIỂN

### Module 1: Auth & User Role Management
- **Mục tiêu:** Màn hình phân quyền nhân viên, đổi chi nhánh làm việc, ghi nhận log hệ thống.
- **Bảng phụ trách:** `users`, `departments`, `roles`, `permissions`, `role_permissions`, `user_roles`, `audit_logs`.

### Module 2 & 3: Table Layout & Menu Management
- **Mục tiêu:** Canvas thiết kế sơ đồ bàn kéo thả, màn hình quản lý thực đơn (món ăn, kích cỡ, nhóm tùy chọn).
- **Bảng phụ trách:** `areas`, `dining_tables`, `categories`, `menu_items`, `item_variants`, `modifier_groups`, `modifiers`, `menu_item_modifier_groups`, `branch_menu_items`.

### Module 4 & 5: POS Ordering & Kitchen Display System (KDS)
- **Mục tiêu:** Giao diện gọi món tại bàn cho Phục vụ + Màn hình KDS cảm ứng tại các quầy bếp.
- **Bảng phụ trách:** `dining_sessions`, `session_tables`, `orders`, `order_batches`, `order_items`, `order_item_modifiers`, `order_status_history`, `stations`, `kitchen_tickets`, `kitchen_ticket_items`, `printers`, `print_jobs`.

### Module 6: Cashier Payment & Invoice
- **Mục tiêu:** Giao diện thu ngân, quét mã QR động, tính tiền thừa, in hóa đơn tạm tính và hóa đơn VAT.
- **Bảng phụ trách:** `payments`, `invoices`, `document_sequences`.

### Module 7: Inventory, Recipe & Stock Movements
- **Mục tiêu:** Quản lý nguyên vật liệu, cấu hình công thức định lượng (BOM), tự động trừ kho theo sổ cái, phiếu nhập hàng và kiểm kê.
- **Bảng phụ trách:** `ingredients`, `warehouses`, `suppliers`, `recipes`, `recipe_items`, `stock_levels`, `stock_movements`, `purchase_orders`, `purchase_order_items`, `stock_adjustments`, `stock_adjustment_items`.

### Module 8 & 9: AI Forecasting, Notification & Analytics Dashboard
- **Mục tiêu:** Bảng điều khiển doanh thu tổng quan, thống kê món bán chạy, module thông báo realtime qua polling, giao diện duyệt đề xuất của AI.
- **Bảng phụ trách:** `ai_runs`, `ai_forecasts`, `ai_alerts`, `approval_requests`, `notifications`, `notification_reads`, `daily_sales_summary`, `daily_item_sales`.
