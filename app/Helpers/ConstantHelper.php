<?php

declare(strict_types=1);

namespace App\Helpers;

final class ConstantHelper
{
    // ==========================================
    // MODULE A: TỔ CHỨC VÀ PHÂN QUYỀN (8 BẢNG)
    // ==========================================
    public const TABLE_BRANCHES = 'branches';

    public const TABLE_DEPARTMENTS = 'departments';

    public const TABLE_USERS = 'users';

    public const TABLE_ROLES = 'roles';

    public const TABLE_PERMISSIONS = 'permissions';

    public const TABLE_ROLE_PERMISSIONS = 'role_permissions';

    public const TABLE_USER_ROLES = 'user_roles';

    public const TABLE_AUDIT_LOGS = 'audit_logs';

    // ==========================================
    // MODULE B: BÀN VÀ LƯỢT DÙNG BỮA (4 BẢNG)
    // ==========================================
    public const TABLE_AREAS = 'areas';

    public const TABLE_DINING_TABLES = 'dining_tables';

    public const TABLE_DINING_SESSIONS = 'dining_sessions';

    public const TABLE_SESSION_TABLES = 'session_tables';

    // ==========================================
    // MODULE C: THỰC ĐƠN (7 BẢNG)
    // ==========================================
    public const TABLE_CATEGORIES = 'categories';

    public const TABLE_MENU_ITEMS = 'menu_items';

    public const TABLE_ITEM_VARIANTS = 'item_variants';

    public const TABLE_MODIFIER_GROUPS = 'modifier_groups';

    public const TABLE_MODIFIERS = 'modifiers';

    public const TABLE_MENU_ITEM_MODIFIER_GROUPS = 'menu_item_modifier_groups';

    public const TABLE_BRANCH_MENU_ITEMS = 'branch_menu_items';

    // ==========================================
    // MODULE D: ORDER (5 BẢNG)
    // ==========================================
    public const TABLE_ORDERS = 'orders';

    public const TABLE_ORDER_BATCHES = 'order_batches';

    public const TABLE_ORDER_ITEMS = 'order_items';

    public const TABLE_ORDER_ITEM_MODIFIERS = 'order_item_modifiers';

    public const TABLE_ORDER_STATUS_HISTORY = 'order_status_history';

    // ==========================================
    // MODULE E: BẾP, KDS VÀ IN ẤN (5 BẢNG)
    // ==========================================
    public const TABLE_PRINTERS = 'printers';

    public const TABLE_STATIONS = 'stations';

    public const TABLE_KITCHEN_TICKETS = 'kitchen_tickets';

    public const TABLE_KITCHEN_TICKET_ITEMS = 'kitchen_ticket_items';

    public const TABLE_PRINT_JOBS = 'print_jobs';

    // ==========================================
    // MODULE F: THANH TOÁN (2 BẢNG)
    // ==========================================
    public const TABLE_PAYMENTS = 'payments';

    public const TABLE_INVOICES = 'invoices';

    // ==========================================
    // MODULE G: KHO (11 BẢNG)
    // ==========================================
    public const TABLE_INGREDIENTS = 'ingredients';

    public const TABLE_WAREHOUSES = 'warehouses';

    public const TABLE_STOCK_LEVELS = 'stock_levels';

    public const TABLE_STOCK_MOVEMENTS = 'stock_movements';

    public const TABLE_SUPPLIERS = 'suppliers';

    public const TABLE_RECIPES = 'recipes';

    public const TABLE_RECIPE_ITEMS = 'recipe_items';

    public const TABLE_PURCHASE_ORDERS = 'purchase_orders';

    public const TABLE_PURCHASE_ORDER_ITEMS = 'purchase_order_items';

    public const TABLE_STOCK_ADJUSTMENTS = 'stock_adjustments';

    public const TABLE_STOCK_ADJUSTMENT_ITEMS = 'stock_adjustment_items';

    // ==========================================
    // MODULE H: AI VÀ PHÊ DUYỆT (4 BẢNG)
    // ==========================================
    public const TABLE_AI_RUNS = 'ai_runs';

    public const TABLE_AI_FORECASTS = 'ai_forecasts';

    public const TABLE_AI_ALERTS = 'ai_alerts';

    public const TABLE_APPROVAL_REQUESTS = 'approval_requests';

    // ==========================================
    // MODULE I: HẠ TẦNG VÀ BÁO CÁO (5 BẢNG)
    // ==========================================
    public const TABLE_DOCUMENT_SEQUENCES = 'document_sequences';

    public const TABLE_NOTIFICATIONS = 'notifications';

    public const TABLE_NOTIFICATION_READS = 'notification_reads';

    public const TABLE_DAILY_SALES_SUMMARY = 'daily_sales_summary';

    public const TABLE_DAILY_ITEM_SALES = 'daily_item_sales';

    // ==========================================
    // MÃ HỆ THỐNG: VAI TRÒ (RoleCode)
    // ==========================================
    public const ROLE_ADMIN = 'ADMIN';

    public const ROLE_BRANCH_MANAGER = 'BRANCH_MANAGER';

    public const ROLE_CASHIER = 'CASHIER';

    public const ROLE_WAITER = 'WAITER';

    public const ROLE_KITCHEN = 'KITCHEN';

    public const ROLE_WAREHOUSE = 'WAREHOUSE';

    // ==========================================
    // MÃ HỆ THỐNG: PHÒNG BAN (DepartmentCode)
    // ==========================================
    public const DEPT_SERVICE = 'SERVICE';

    public const DEPT_KITCHEN = 'KITCHEN';

    public const DEPT_CASHIER = 'CASHIER';

    public const DEPT_WAREHOUSE = 'WAREHOUSE';

    public const DEPT_OFFICE = 'OFFICE';

    // ==========================================
    // MÃ HỆ THỐNG: LOẠI CHỨNG TỪ (DocType)
    // ==========================================
    public const DOC_ORDER = 'ORDER';

    public const DOC_INVOICE = 'INVOICE';

    public const DOC_KITCHEN_TICKET = 'KITCHEN_TICKET';

    public const DOC_PURCHASE_ORDER = 'PURCHASE_ORDER';

    public const DOC_STOCK_ADJUSTMENT = 'STOCK_ADJUSTMENT';

    // ==========================================
    // MÃ QUYỀN HẠN (PERMISSIONS)
    // ==========================================
    // Auth / User
    public const PERM_USER_VIEW = 'user.view';

    public const PERM_USER_MANAGE = 'user.manage';

    public const PERM_USER_ROLE_ASSIGN = 'user.role.assign';

    public const PERM_ROLE_MANAGE = 'role.manage';

    public const PERM_PERMISSION_VIEW = 'permission.view';

    public const PERM_AUDIT_VIEW = 'audit.view';

    // Tổ chức
    public const PERM_BRANCH_MANAGE = 'branch.manage';

    public const PERM_DEPARTMENT_MANAGE = 'department.manage';

    // Bàn / Lượt dùng bữa
    public const PERM_TABLE_VIEW = 'table.view';

    public const PERM_TABLE_LAYOUT_MANAGE = 'table.layout.manage';

    public const PERM_SESSION_MANAGE = 'session.manage';

    // Thực đơn
    public const PERM_MENU_VIEW = 'menu.view';

    public const PERM_MENU_MANAGE = 'menu.manage';

    public const PERM_MENU_AVAILABILITY_UPDATE = 'menu.availability.update';

    // Order
    public const PERM_ORDER_VIEW = 'order.view';

    public const PERM_ORDER_CREATE = 'order.create';

    public const PERM_ORDER_UPDATE = 'order.update';

    public const PERM_ORDER_CANCEL = 'order.cancel';

    public const PERM_ORDER_SERVE = 'order.serve';

    public const PERM_ORDER_BATCH_RELEASE = 'order.batch.release';

    // Bếp
    public const PERM_KITCHEN_VIEW = 'kitchen.view';

    public const PERM_KITCHEN_UPDATE_STATUS = 'kitchen.update_status';

    public const PERM_KITCHEN_PRINT = 'kitchen.print';

    // Thanh toán
    public const PERM_PAYMENT_REQUEST = 'payment.request';

    public const PERM_PAYMENT_CONFIRM = 'payment.confirm';

    public const PERM_INVOICE_PRINT = 'invoice.print';

    // Kho
    public const PERM_INVENTORY_VIEW = 'inventory.view';

    public const PERM_INVENTORY_RECEIVE_ISSUE = 'inventory.receive_issue';

    public const PERM_INVENTORY_ADJUST = 'inventory.adjust';

    public const PERM_INVENTORY_APPROVE = 'inventory.approve';

    public const PERM_RECIPE_MANAGE = 'recipe.manage';

    public const PERM_PURCHASE_CREATE = 'purchase.create';

    public const PERM_SUPPLIER_MANAGE = 'supplier.manage';

    // AI / Duyệt / Báo cáo
    public const PERM_AI_VIEW = 'ai.view';

    public const PERM_AI_RUN = 'ai.run';

    public const PERM_AI_APPROVE = 'ai.approve';

    public const PERM_REPORT_VIEW = 'report.view';

    /**
     * Danh sách toàn bộ 51 bảng nghiệp vụ.
     *
     * @return array<int, string>
     */
    public static function allTables(): array
    {
        return [
            // Module A
            self::TABLE_BRANCHES,
            self::TABLE_DEPARTMENTS,
            self::TABLE_USERS,
            self::TABLE_ROLES,
            self::TABLE_PERMISSIONS,
            self::TABLE_ROLE_PERMISSIONS,
            self::TABLE_USER_ROLES,
            self::TABLE_AUDIT_LOGS,

            // Module B
            self::TABLE_AREAS,
            self::TABLE_DINING_TABLES,
            self::TABLE_DINING_SESSIONS,
            self::TABLE_SESSION_TABLES,

            // Module C
            self::TABLE_CATEGORIES,
            self::TABLE_MENU_ITEMS,
            self::TABLE_ITEM_VARIANTS,
            self::TABLE_MODIFIER_GROUPS,
            self::TABLE_MODIFIERS,
            self::TABLE_MENU_ITEM_MODIFIER_GROUPS,
            self::TABLE_BRANCH_MENU_ITEMS,

            // Module D
            self::TABLE_ORDERS,
            self::TABLE_ORDER_BATCHES,
            self::TABLE_ORDER_ITEMS,
            self::TABLE_ORDER_ITEM_MODIFIERS,
            self::TABLE_ORDER_STATUS_HISTORY,

            // Module E
            self::TABLE_PRINTERS,
            self::TABLE_STATIONS,
            self::TABLE_KITCHEN_TICKETS,
            self::TABLE_KITCHEN_TICKET_ITEMS,
            self::TABLE_PRINT_JOBS,

            // Module F
            self::TABLE_PAYMENTS,
            self::TABLE_INVOICES,

            // Module G
            self::TABLE_INGREDIENTS,
            self::TABLE_WAREHOUSES,
            self::TABLE_STOCK_LEVELS,
            self::TABLE_STOCK_MOVEMENTS,
            self::TABLE_SUPPLIERS,
            self::TABLE_RECIPES,
            self::TABLE_RECIPE_ITEMS,
            self::TABLE_PURCHASE_ORDERS,
            self::TABLE_PURCHASE_ORDER_ITEMS,
            self::TABLE_STOCK_ADJUSTMENTS,
            self::TABLE_STOCK_ADJUSTMENT_ITEMS,

            // Module H
            self::TABLE_AI_RUNS,
            self::TABLE_AI_FORECASTS,
            self::TABLE_AI_ALERTS,
            self::TABLE_APPROVAL_REQUESTS,

            // Module I
            self::TABLE_DOCUMENT_SEQUENCES,
            self::TABLE_NOTIFICATIONS,
            self::TABLE_NOTIFICATION_READS,
            self::TABLE_DAILY_SALES_SUMMARY,
            self::TABLE_DAILY_ITEM_SALES,
        ];
    }

    /**
     * Danh sách toàn bộ quyền hạn hệ thống [code => [module, label_vi]].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function allPermissions(): array
    {
        return [
            // auth/user
            self::PERM_USER_VIEW => ['auth_user', 'Xem danh sách người dùng'],
            self::PERM_USER_MANAGE => ['auth_user', 'Quản lý người dùng'],
            self::PERM_USER_ROLE_ASSIGN => ['auth_user', 'Gán chức vụ cho người dùng'],
            self::PERM_ROLE_MANAGE => ['auth_user', 'Quản lý chức vụ và quyền'],
            self::PERM_PERMISSION_VIEW => ['auth_user', 'Xem danh mục quyền hạn'],
            self::PERM_AUDIT_VIEW => ['auth_user', 'Xem nhật ký kiểm toán hệ thống'],

            // tổ chức
            self::PERM_BRANCH_MANAGE => ['organization', 'Quản lý chi nhánh'],
            self::PERM_DEPARTMENT_MANAGE => ['organization', 'Quản lý phòng ban'],

            // bàn/lượt
            self::PERM_TABLE_VIEW => ['table_session', 'Xem sơ đồ bàn'],
            self::PERM_TABLE_LAYOUT_MANAGE => ['table_session', 'Quản lý bố trí bàn & khu vực'],
            self::PERM_SESSION_MANAGE => ['table_session', 'Mở, đóng, chuyển gộp bàn'],

            // thực đơn
            self::PERM_MENU_VIEW => ['menu', 'Xem thực đơn'],
            self::PERM_MENU_MANAGE => ['menu', 'Quản lý món ăn, phân loại, topping'],
            self::PERM_MENU_AVAILABILITY_UPDATE => ['menu', 'Cập nhật trạng thái hết hàng tạm thời'],

            // order
            self::PERM_ORDER_VIEW => ['order', 'Xem đơn gọi món'],
            self::PERM_ORDER_CREATE => ['order', 'Tạo đơn gọi món mới'],
            self::PERM_ORDER_UPDATE => ['order', 'Cập nhật món trong đơn'],
            self::PERM_ORDER_CANCEL => ['order', 'Hủy món hoặc đơn'],
            self::PERM_ORDER_SERVE => ['order', 'Xác nhận phục vụ món'],
            self::PERM_ORDER_BATCH_RELEASE => ['order', 'Xả đợt gọi món xuống bếp'],

            // bếp
            self::PERM_KITCHEN_VIEW => ['kitchen', 'Xem màn hình điều phối bếp (KDS)'],
            self::PERM_KITCHEN_UPDATE_STATUS => ['kitchen', 'Cập nhật trạng thái chế biến món'],
            self::PERM_KITCHEN_PRINT => ['kitchen', 'In phiếu bếp'],

            // thanh toán
            self::PERM_PAYMENT_REQUEST => ['payment', 'Gửi yêu cầu thanh toán'],
            self::PERM_PAYMENT_CONFIRM => ['payment', 'Xác nhận thu tiền / thanh toán'],
            self::PERM_INVOICE_PRINT => ['payment', 'In hóa đơn'],

            // kho
            self::PERM_INVENTORY_VIEW => ['inventory', 'Xem tồn kho'],
            self::PERM_INVENTORY_RECEIVE_ISSUE => ['inventory', 'Nhập / Xuất kho'],
            self::PERM_INVENTORY_ADJUST => ['inventory', 'Tạo phiếu kiểm kê / điều chỉnh kho'],
            self::PERM_INVENTORY_APPROVE => ['inventory', 'Duyệt phiếu kiểm kê / nhập hàng'],
            self::PERM_RECIPE_MANAGE => ['inventory', 'Quản lý công thức định lượng món'],
            self::PERM_PURCHASE_CREATE => ['inventory', 'Tạo đơn nhập hàng'],
            self::PERM_SUPPLIER_MANAGE => ['inventory', 'Quản lý nhà cung cấp'],

            // AI/duyệt/báo cáo
            self::PERM_AI_VIEW => ['ai_report', 'Xem dự báo & cảnh báo AI'],
            self::PERM_AI_RUN => ['ai_report', 'Kích hoạt mô hình phân tích / dự báo AI'],
            self::PERM_AI_APPROVE => ['ai_report', 'Phê duyệt đề xuất AI'],
            self::PERM_REPORT_VIEW => ['ai_report', 'Xem báo cáo doanh thu & kinh doanh'],
        ];
    }
}
