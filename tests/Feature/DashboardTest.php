<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    /** @test */
    public function trang_dashboard_truy_cap_thanh_cong()
    {
        // Gửi yêu cầu GET tới đường dẫn /dashboard
        $response = $this->get('/dashboard');

        // Xác thực trang web phản hồi mã trạng thái 200 (Thành công)
        $response->assertStatus(200);
    }

    /** @test */
    public function trang_bao_cao_truy_cap_thanh_cong()
    {
        // Gửi yêu cầu GET tới đường dẫn /admin/reports kèm theo bộ lọc ngày hợp lệ
        $response = $this->get('/admin/reports?from_date=2026-10-01&to_date=2026-10-16');

        // Xác thực trang báo cáo phản hồi thành công
        $response->assertStatus(200);
    }
}
