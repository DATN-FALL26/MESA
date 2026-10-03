# SƠ ĐỒ QUAN HỆ THỰC THỂ (ENTITY RELATIONSHIP DIAGRAM - ERD)
**Hệ thống Quản lý Vận hành Chuỗi Quán Phở (MESA POS & Operation System)**

---

## 1. SƠ ĐỒ TỔNG QUAN HỆ THỐNG (SYSTEM OVERVIEW ERD)

Sơ đồ thể hiện luồng liên kết trung tâm giữa 9 phân hệ: **Tổ chức & Phân quyền**, **Bàn & Lượt dùng**, **Thực đơn**, **Gọi món (Order)**, **Bếp & KDS**, **Thanh toán**, **Kho & Định lượng**, **AI & Dự báo**, **Hạ tầng & Báo cáo**.

```mermaid
erDiagram
    BRANCHES ||--o{ AREAS : "chia khu"
    BRANCHES ||--o{ DINING_TABLES : "sở hữu"
    BRANCHES ||--o{ WAREHOUSES : "quản lý kho"
    BRANCHES ||--o{ STATIONS : "bố trí quầy"
    BRANCHES ||--o{ PRINTERS : "kết nối"
    BRANCHES ||--o{ ORDERS : "phát sinh"
    BRANCHES ||--o{ DOCUMENT_SEQUENCES : "sinh mã"

    USERS ||--o{ USER_ROLES : "gán chức vụ"
    ROLES ||--o{ USER_ROLES : "vai trò"
    ROLES ||--o{ ROLE_PERMISSIONS : "chứa quyền"
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : "quyền hạn"

    AREAS ||--o{ DINING_TABLES : "chứa bàn"
    DINING_SESSIONS ||--o{ SESSION_TABLES : "gán bàn"
    DINING_TABLES ||--o{ SESSION_TABLES : "tham gia lượt"
    DINING_SESSIONS ||--o{ ORDERS : "phát sinh order"

    CATEGORIES ||--o{ MENU_ITEMS : "phân loại"
    MENU_ITEMS ||--o{ ITEM_VARIANTS : "kích cỡ"
    MENU_ITEMS ||--o{ MENU_ITEM_MODIFIER_GROUPS : "gán nhóm tùy chọn"
    MODIFIER_GROUPS ||--o{ MENU_ITEM_MODIFIER_GROUPS : "thuộc món"
    MODIFIER_GROUPS ||--o{ MODIFIERS : "chứa tùy chọn"

    ORDERS ||--o{ ORDER_BATCHES : "chia đợt"
    ORDER_BATCHES ||--o{ ORDER_ITEMS : "gồm các món"
    ORDER_ITEMS ||--o{ ORDER_ITEM_MODIFIERS : "kèm topping"
    ORDERS ||--o{ PAYMENTS : "thanh toán"
    ORDERS ||--o{ INVOICES : "xuất hóa đơn"

    ORDER_BATCHES ||--o{ KITCHEN_TICKETS : "gửi bếp"
    STATIONS ||--o{ KITCHEN_TICKETS : "tiếp nhận"
    KITCHEN_TICKETS ||--o{ KITCHEN_TICKET_ITEMS : "dòng chế biến"
    ORDER_ITEMS ||--o{ KITCHEN_TICKET_ITEMS : "liên kết món"

    WAREHOUSES ||--o{ STOCK_LEVELS : "tồn kho"
    INGREDIENTS ||--o{ STOCK_LEVELS : "số lượng"
    WAREHOUSES ||--o{ STOCK_MOVEMENTS : "sổ cái nhập xuất"
    INGREDIENTS ||--o{ STOCK_MOVEMENTS : "nguyên liệu"
    MENU_ITEMS ||--o{ RECIPES : "định lượng"
    ITEM_VARIANTS ||--o{ RECIPES : "theo size"
    RECIPES ||--o{ RECIPE_ITEMS : "gồm nguyên liệu"
    INGREDIENTS ||--o{ RECIPE_ITEMS : "tiêu hao"

    AI_RUNS ||--o{ AI_FORECASTS : "kết quả dự báo"
    BRANCHES ||--o{ AI_ALERTS : "cảnh báo thiếu hụt"
    BRANCHES ||--o{ APPROVAL_REQUESTS : "yêu cầu phê duyệt"
```

---

## 2. SƠ ĐỒ CHI TIẾT TỪNG PHÂN HỆ

### Nhóm A: Tổ chức và Phân quyền (Organization & Auth)
```mermaid
erDiagram
    BRANCHES {
        bigint id PK
        string code UK "Mã chi nhánh"
        string name "Tên chi nhánh"
        string address "Địa chỉ"
        string timezone "Múi giờ"
        enum status "active, inactive"
    }

    DEPARTMENTS {
        bigint id PK
        string code UK "SERVICE, KITCHEN..."
        string name "Tên phòng ban"
        int sort_order
        boolean is_active
    }

    USERS {
        bigint id PK
        string username UK
        string employee_code UK
        string full_name
        string email UK
        string password
        string pin_hash
        bigint department_id FK
        enum status "active, locked, inactive"
    }

    ROLES {
        bigint id PK
        string code UK "ADMIN, CASHIER..."
        string name
        boolean is_system
    }

    PERMISSIONS {
        bigint id PK
        string code UK "user.view, order.create..."
        string module
        string name
    }

    ROLE_PERMISSIONS {
        bigint role_id PK, FK
        bigint permission_id PK, FK
    }

    USER_ROLES {
        bigint id PK
        bigint user_id FK
        bigint role_id FK
        enum scope_type "ALL, BRANCH"
        bigint scope_id "ID chi nhánh khi BRANCH"
        datetime valid_from
        datetime valid_to
    }

    AUDIT_LOGS {
        bigint id PK
        bigint branch_id
        bigint user_id
        string action
        string entity_type
        bigint entity_id
        json old_value
        json new_value
        string ip_address
    }

    DEPARTMENTS ||--o{ USERS : "thuộc phòng ban"
    USERS ||--o{ USER_ROLES : "được gán"
    ROLES ||--o{ USER_ROLES : "vai trò"
    ROLES ||--o{ ROLE_PERMISSIONS : "chứa"
    PERMISSIONS ||--o{ ROLE_PERMISSIONS : "quyền hạn"
```

---

### Nhóm B: Bàn và Lượt Dùng Bữa (Table & Dining Session)
```mermaid
erDiagram
    BRANCHES ||--o{ AREAS : "sở hữu"
    BRANCHES ||--o{ DINING_TABLES : "quản lý"
    AREAS ||--o{ DINING_TABLES : "phân bố bàn"
    BRANCHES ||--o{ DINING_SESSIONS : "phát sinh"
    DINING_SESSIONS ||--o{ SESSION_TABLES : "gán bàn"
    DINING_TABLES ||--o{ SESSION_TABLES : "tham gia lượt"

    AREAS {
        bigint id PK
        bigint branch_id FK
        string code "Mã khu: A, B..."
        string name "Khu A, Khu B..."
        smallint default_seats
        json layout_config
        boolean is_active
    }

    DINING_TABLES {
        bigint id PK
        bigint branch_id FK
        bigint area_id FK
        string code "A01, B02..."
        smallint seats
        enum shape "square, round, rect"
        int pos_x
        int pos_y
        enum status "available, occupied, reserved, cleaning"
        boolean is_active
    }

    DINING_SESSIONS {
        bigint id PK
        bigint branch_id FK
        bigint opened_by FK
        datetime opened_at
        datetime closed_at
        smallint guest_count
        enum status "open, closed, cancelled"
        string note
    }

    SESSION_TABLES {
        bigint id PK
        bigint session_id FK
        bigint table_id FK
        datetime joined_at
        datetime left_at
        bigint open_guard UK "Generated cột khóa"
    }
```

---

### Nhóm C: Thực Đơn & Tùy Chọn (Menu & Modifiers)
```mermaid
erDiagram
    CATEGORIES ||--o{ MENU_ITEMS : "phân loại"
    CATEGORIES ||--o{ CATEGORIES : "danh mục cha/con"
    MENU_ITEMS ||--o{ ITEM_VARIANTS : "biến thể size"
    MENU_ITEMS ||--o{ MENU_ITEM_MODIFIER_GROUPS : "gán nhóm"
    MODIFIER_GROUPS ||--o{ MENU_ITEM_MODIFIER_GROUPS : "liên kết"
    MODIFIER_GROUPS ||--o{ MODIFIERS : "chứa option"
    BRANCHES ||--o{ BRANCH_MENU_ITEMS : "áp dụng tại"
    MENU_ITEMS ||--o{ BRANCH_MENU_ITEMS : "ghi đè giá/hàng"

    CATEGORIES {
        bigint id PK
        bigint parent_id FK
        string name
        int sort_order
        boolean is_active
    }

    MENU_ITEMS {
        bigint id PK
        bigint category_id FK
        string sku UK
        string name
        enum item_type "dish, drink, side, retail"
        enum station_code "PHO, DRINK, SIDE"
        decimal base_price
        decimal tax_rate
        int prep_time_seconds
        boolean is_active
    }

    ITEM_VARIANTS {
        bigint id PK
        bigint menu_item_id FK
        string name "Nhỏ, Lớn, Đặc biệt"
        decimal price_delta
        boolean is_default
    }

    MODIFIER_GROUPS {
        bigint id PK
        string name "Thêm thịt, Topping..."
        smallint min_select
        smallint max_select
    }

    MODIFIERS {
        bigint id PK
        bigint group_id FK
        string name
        decimal price
        bigint ingredient_id FK
        decimal ingredient_qty
    }

    MENU_ITEM_MODIFIER_GROUPS {
        bigint menu_item_id PK, FK
        bigint group_id PK, FK
        int sort_order
    }

    BRANCH_MENU_ITEMS {
        bigint id PK
        bigint branch_id FK
        bigint menu_item_id FK
        decimal price "NULL = base_price"
        boolean is_available
    }
```

---

### Nhóm D: Gọi Món (Order & Order Batches)
```mermaid
erDiagram
    DINING_SESSIONS ||--o{ ORDERS : "phát sinh"
    ORDERS ||--o{ ORDER_BATCHES : "chia đợt"
    ORDERS ||--o{ ORDER_ITEMS : "chứa món"
    ORDER_BATCHES ||--o{ ORDER_ITEMS : "thuộc đợt"
    ORDER_ITEMS ||--o{ ORDER_ITEM_MODIFIERS : "kèm topping"
    ORDERS ||--o{ ORDER_STATUS_HISTORY : "lịch sử trạng thái"

    ORDERS {
        bigint id PK
        bigint branch_id FK
        bigint session_id FK
        string order_no
        enum channel "dine_in, takeaway"
        enum status "draft, sent, in_progress, served, payment_requested, completed, cancelled"
        decimal subtotal
        decimal discount_amount
        decimal tax_amount
        decimal total_amount
        bigint created_by FK
    }

    ORDER_BATCHES {
        bigint id PK
        bigint order_id FK
        smallint batch_no
        enum serve_mode "together, as_ready"
        enum status "open, sent, cooking, partially_ready, all_ready, served, cancelled"
        datetime sent_at
        datetime target_ready_at
        datetime all_ready_at
    }

    ORDER_ITEMS {
        bigint id PK
        bigint order_id FK
        bigint batch_id FK
        bigint menu_item_id FK
        bigint variant_id FK
        string item_name_snapshot
        string variant_name_snapshot
        decimal unit_price_snapshot
        smallint quantity
        decimal line_total
        enum status "pending, sent, preparing, ready, served, cancelled"
        datetime stock_deducted_at
    }

    ORDER_ITEM_MODIFIERS {
        bigint id PK
        bigint order_item_id FK
        bigint modifier_id FK
        string name_snapshot
        decimal price_snapshot
        smallint quantity
    }

    ORDER_STATUS_HISTORY {
        bigint id PK
        bigint order_id FK
        bigint order_item_id FK
        bigint batch_id FK
        string from_status
        string to_status
        bigint changed_by FK
    }
```

---

### Nhóm E: Bếp, KDS và In Ấn (Kitchen, KDS & Printing)
```mermaid
erDiagram
    BRANCHES ||--o{ PRINTERS : "trang bị"
    BRANCHES ||--o{ STATIONS : "thiết lập quầy"
    PRINTERS ||--o{ STATIONS : "kết nối máy in"
    PRINTERS ||--o{ PRINT_JOBS : "thực thi lệnh in"
    STATIONS ||--o{ KITCHEN_TICKETS : "tiếp nhận ticket"
    ORDER_BATCHES ||--o{ KITCHEN_TICKETS : "gửi đợt"
    KITCHEN_TICKETS ||--o{ KITCHEN_TICKET_ITEMS : "dòng chế biến"
    ORDER_ITEMS ||--o{ KITCHEN_TICKET_ITEMS : "liên kết món"

    PRINTERS {
        bigint id PK
        bigint branch_id FK
        string name
        enum type "kitchen, receipt"
        string connection
        boolean is_active
    }

    STATIONS {
        bigint id PK
        bigint branch_id FK
        enum station_code "PHO, DRINK, SIDE"
        string name
        bigint printer_id FK
        boolean is_active
    }

    KITCHEN_TICKETS {
        bigint id PK
        bigint branch_id FK
        bigint order_id FK
        bigint batch_id FK
        bigint station_id FK
        string ticket_no
        enum status "new, preparing, ready, done, cancelled"
        datetime fired_at
        datetime ready_at
    }

    KITCHEN_TICKET_ITEMS {
        bigint id PK
        bigint ticket_id FK
        bigint order_item_id FK
        enum status "new, scheduled, preparing, ready, cancelled"
        datetime fire_at
        datetime ready_at
    }

    PRINT_JOBS {
        bigint id PK
        bigint printer_id FK
        enum doc_type "kitchen_ticket, invoice, payment_request"
        bigint ref_id
        json payload
        enum status "queued, printed, failed"
        tinyint retry_count
    }
```

---

### Nhóm F: Thanh Toán và Hóa Đơn (Payment & Invoice)
```mermaid
erDiagram
    ORDERS ||--o{ PAYMENTS : "thanh toán nhiều đợt/kênh"
    ORDERS ||--o{ INVOICES : "xuất hóa đơn"

    PAYMENTS {
        bigint id PK
        bigint branch_id FK
        bigint order_id FK
        enum method "cash, qr, card"
        decimal amount
        decimal tendered_amount
        decimal change_amount
        enum status "pending, success, failed, refunded"
        string gateway_txn_id UK
        json gateway_payload
        bigint confirmed_by FK
        datetime paid_at
    }

    INVOICES {
        bigint id PK
        bigint branch_id FK
        bigint order_id FK
        string invoice_no
        datetime issued_at
        decimal total_amount
        json buyer_info
        enum status "issued, void"
        smallint print_count
    }
```

---

### Nhóm G: Kho, Định Lượng & Nhập Hàng (Inventory & Recipe)
```mermaid
erDiagram
    BRANCHES ||--o{ WAREHOUSES : "quản lý kho"
    WAREHOUSES ||--o{ STOCK_LEVELS : "tồn kho hiện thời"
    INGREDIENTS ||--o{ STOCK_LEVELS : "nguyên liệu"
    WAREHOUSES ||--o{ STOCK_MOVEMENTS : "sổ cái biến động"
    INGREDIENTS ||--o{ STOCK_MOVEMENTS : "nhập/xuất/tiêu hao"
    
    MENU_ITEMS ||--o{ RECIPES : "công thức"
    ITEM_VARIANTS ||--o{ RECIPES : "theo biến thể size"
    RECIPES ||--o{ RECIPE_ITEMS : "định lượng nguyên liệu"
    INGREDIENTS ||--o{ RECIPE_ITEMS : "đơn vị tiêu hao"

    SUPPLIERS ||--o{ PURCHASE_ORDERS : "cung ứng"
    WAREHOUSES ||--o{ PURCHASE_ORDERS : "nhập về kho"
    PURCHASE_ORDERS ||--o{ PURCHASE_ORDER_ITEMS : "gồm nguyên liệu"
    INGREDIENTS ||--o{ PURCHASE_ORDER_ITEMS : "mặt hàng"

    WAREHOUSES ||--o{ STOCK_ADJUSTMENTS : "kiểm kê"
    STOCK_ADJUSTMENTS ||--o{ STOCK_ADJUSTMENT_ITEMS : "chênh lệch tồn"
    INGREDIENTS ||--o{ STOCK_ADJUSTMENT_ITEMS : "kiểm đếm"

    INGREDIENTS {
        bigint id PK
        string sku UK
        string name
        enum ingredient_type "raw, semi_finished"
        string base_uom "g, ml, cái..."
        decimal min_stock
        boolean is_active
    }

    WAREHOUSES {
        bigint id PK
        bigint branch_id FK
        string name
        enum type "branch, central"
        boolean is_active
    }

    STOCK_LEVELS {
        bigint id PK
        bigint warehouse_id FK
        bigint ingredient_id FK
        decimal quantity
        decimal avg_cost
    }

    STOCK_MOVEMENTS {
        bigint id PK
        bigint warehouse_id FK
        bigint ingredient_id FK
        enum movement_type "purchase_in, sale_out, sale_return, adjust, waste"
        decimal quantity
        decimal unit_cost
        string ref_type
        bigint ref_id
        string idempotency_key UK
    }

    RECIPES {
        bigint id PK
        bigint menu_item_id FK
        bigint variant_id FK
        int version
        boolean is_active
    }

    RECIPE_ITEMS {
        bigint id PK
        bigint recipe_id FK
        bigint ingredient_id FK
        decimal quantity
    }
```

---

### Nhóm H: AI, Dự Báo & Phê Duyệt (AI & Approvals)
```mermaid
erDiagram
    AI_RUNS ||--o{ AI_FORECASTS : "kết quả dự báo"
    BRANCHES ||--o{ AI_RUNS : "phân tích theo cơ sở"
    BRANCHES ||--o{ AI_ALERTS : "cảnh báo vận hành"
    BRANCHES ||--o{ APPROVAL_REQUESTS : "xét duyệt nghiệp vụ"

    AI_RUNS {
        bigint id PK
        bigint branch_id FK
        enum run_type "sales_analysis, demand_forecast, shortage_detection"
        json params
        enum status "queued, running, done, failed"
        json result_summary
        datetime started_at
        datetime finished_at
    }

    AI_FORECASTS {
        bigint id PK
        bigint ai_run_id FK
        bigint branch_id FK
        bigint menu_item_id FK
        bigint ingredient_id FK
        date forecast_date
        decimal predicted_qty
        decimal confidence
        string model_version
    }

    AI_ALERTS {
        bigint id PK
        bigint branch_id FK
        enum alert_type "low_stock_risk, sales_anomaly"
        enum severity "info, warning, critical"
        bigint ingredient_id FK
        json payload
        enum status "new, acknowledged, resolved"
    }

    APPROVAL_REQUESTS {
        bigint id PK
        bigint branch_id FK
        enum request_type "purchase_order, stock_adjustment, ai_suggestion"
        bigint ref_id "ID đa hình"
        bigint requested_by FK
        enum status "pending, approved, rejected"
        bigint decided_by FK
    }
```

---

### Nhóm I: Hạ Tầng, Thông Báo & Dashboard (Infrastructure & Reporting)
```mermaid
erDiagram
    BRANCHES ||--o{ DOCUMENT_SEQUENCES : "quản lý chuỗi số"
    BRANCHES ||--o{ NOTIFICATIONS : "thông báo chi nhánh"
    NOTIFICATIONS ||--o{ NOTIFICATION_READS : "trạng thái đọc"
    USERS ||--o{ NOTIFICATION_READS : "nhân viên đã xem"
    BRANCHES ||--o{ DAILY_SALES_SUMMARY : "tổng kết ngày"
    BRANCHES ||--o{ DAILY_ITEM_SALES : "món bán chạy ngày"
    MENU_ITEMS ||--o{ DAILY_ITEM_SALES : "doanh thu món"

    DOCUMENT_SEQUENCES {
        bigint id PK
        bigint branch_id FK
        string doc_type "ORDER, INVOICE, PO..."
        string period_key "20261003"
        string prefix "ORD, INV..."
        bigint current_no
        tinyint padding
        enum reset_policy "never, daily, monthly, yearly"
    }

    NOTIFICATIONS {
        bigint id PK
        bigint branch_id FK
        bigint target_user_id FK
        string target_role_code
        enum type "batch_all_ready, payment_request..."
        string title
        string body
        json payload
        datetime expires_at
    }

    DAILY_SALES_SUMMARY {
        bigint id PK
        bigint branch_id FK
        date sales_date
        int order_count
        int guest_count
        decimal gross_revenue
        decimal discount_amount
        decimal net_revenue
        json payment_breakdown
    }

    DAILY_ITEM_SALES {
        bigint id PK
        bigint branch_id FK
        date sales_date
        bigint menu_item_id FK
        int quantity
        decimal gross_revenue
    }
```
