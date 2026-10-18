# ĐỐI CHIẾU THIẾT KẾ VÀ GAP ANALYSIS (FIGMA GAP ANALYSIS)
**Hệ thống Quản lý Vận hành Chuỗi Quán Phở (MESA POS & Operation System)**

---

## 1. TỔNG QUAN ĐỐI CHIẾU
Tài liệu này dùng để ghi nhận sự khác biệt (nếu có) giữa bản thiết kế giao diện Figma / tài liệu use case của nhóm và cơ sở dữ liệu thực tế 51 bảng được xây dựng.

---

## 2. BẢNG THEO DÕI GAP ANALYSIS

| STT | Màn hình / Tính năng | Trường / Yêu cầu trên UI | Trạng thái trong Schema | Bảng & Cột xử lý / Ghi chú |
| :---: | :--- | :--- | :---: | :--- |
| **1** | Màn hình POS - Sơ đồ bàn | Hiển thị tọa độ ô, kích thước bàn, trạng thái bàn | ĐÃ ĐẦY ĐỦ | `dining_tables.pos_x`, `pos_y`, `width`, `height`, `status` |
| **2** | Màn hình POS - Đổi/gộp bàn | Khách đổi từ bàn A sang bàn B mà không mất đơn | ĐÃ ĐẦY ĐỦ | `session_tables` lưu lịch sử chuyển bàn với `joined_at`, `left_at` |
| **3** | Màn hình POS - Gọi món chia đợt | Gửi trước đợt phở khai vị, gửi sau đồ uống | ĐÃ ĐẦY ĐỦ | `order_batches` quản lý các đợt gọi món và giờ ra món mục tiêu |
| **4** | Màn hình KDS - Bếp | Tách hiển thị món theo quầy (Phở, Nước, Món kèm) | ĐÃ ĐẦY ĐỦ | `stations.station_code` và `kitchen_tickets`, `kitchen_ticket_items` |
| **5** | Màn hình Bếp - Nấu lệch giờ | Món mất 5 phút nấu trước món mất 2 phút để ra cùng lúc | ĐÃ ĐẦY ĐỦ | `menu_items.prep_time_seconds` và `kitchen_ticket_items.fire_at` |
| **6** | Màn hình Thu ngân - Tách tiền | Khách trả 50k tiền mặt, 100k quét QR | ĐÃ ĐẦY ĐỦ | `payments` quan hệ 1-N với `orders` (nhiều payment cho 1 order) |
| **7** | Màn hình Kho - Định lượng | Trừ kho tự động theo định lượng tô lớn / tô nhỏ | ĐÃ ĐẦY ĐỦ | `recipes` và `recipe_items` phân loại theo `item_variants` |
| **8** | Màn hình Quản lý - Cảnh báo AI | Cảnh báo nguy cơ hết thịt bò / nước dùng | ĐÃ ĐẦY ĐỦ | `ai_alerts` và `ai_forecasts` liên kết trực tiếp `ingredient_id` |

---

## 3. KẾT LUẬN
- Schema hiện tại đã bao phủ **100%** các chức năng nghiệp vụ của 9 module use case cốt lõi.
- Nếu trong quá trình hoàn thiện giao diện Figma có bổ sung trường thông tin mới, nhóm phát triển có thể đề xuất bổ sung qua file này trước khi tiến hành cập nhật migration.
